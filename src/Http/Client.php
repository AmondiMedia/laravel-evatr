<?php

namespace AmondiMedia\LaravelEvatr\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Client
{
    protected GuzzleClient $httpClient;

    protected string $apiUrl;

    protected ?string $requesterVatId;

    protected int $timeout;

    public function __construct(
        string $apiUrl,
        ?string $requesterVatId,
        int $timeout = 10,
        ?GuzzleClient $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleClient;
        $this->apiUrl = rtrim($apiUrl, '/').'/';
        $this->requesterVatId = $requesterVatId;
        $this->timeout = $timeout;
    }

    public function validate(
        string $vatIdToValidate,
        ?string $companyName = null,
        ?string $city = null,
        ?string $zipCode = null,
        ?string $street = null
    ): array {
        if (empty($this->requesterVatId)) {
            return ['valid' => false, 'data' => null, 'error' => 'Requester VAT ID (UstId_1) is not configured.', 'error_code' => 'CONFIG_ERROR'];
        }

        $xmlRequest = $this->buildXmlRpcRequest(
            $this->requesterVatId,
            $vatIdToValidate,
            $companyName ?? '',
            $city ?? '',
            $zipCode ?? '',
            $street ?? '',
            'nein'
        );

        try {
            $response = $this->httpClient->post($this->apiUrl, [
                'headers' => ['Content-Type' => 'text/xml; charset=UTF-8'],
                'body' => $xmlRequest,
                'timeout' => $this->timeout,
            ]);

            $xmlResponse = (string) $response->getBody();

            return $this->parseXmlRpcResponse($xmlResponse);

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'No response body';
            Log::error('eVatR API request failed: '.$e->getMessage(), [
                'request_url' => $this->apiUrl,
                'request_body' => $xmlRequest,
                'response_body' => $responseBody,
            ]);
            if ($e->hasResponse() && str_contains($responseBody, '<fault>')) {
                return $this->parseXmlRpcResponse($responseBody);
            }

            return ['valid' => false, 'data' => null, 'error' => 'API request failed: '.$e->getMessage(), 'error_code' => 'REQUEST_EXCEPTION'];
        } catch (\Exception $e) {
            Log::error('eVatR API general error: '.$e->getMessage(), [
                'request_url' => $this->apiUrl,
                'request_body' => $xmlRequest,
            ]);

            return ['valid' => false, 'data' => null, 'error' => 'General error processing VAT request: '.$e->getMessage(), 'error_code' => 'GENERAL_EXCEPTION'];
        }
    }

    protected function buildXmlRpcRequest(
        string $ustId1,
        string $ustId2,
        string $companyName,
        string $city,
        string $zipCode,
        string $street,
        string $druck
    ): string {
        $xml = new \SimpleXMLElement('<methodCall/>');
        $xml->addChild('methodName', 'evatrRPC');

        $paramsNode = $xml->addChild('params');
        $orderedParams = [$ustId1, $ustId2, $companyName, $city, $zipCode, $street, $druck];

        foreach ($orderedParams as $value) {
            $paramNode = $paramsNode->addChild('param');
            $valueNode = $paramNode->addChild('value');
            $valueNode->addChild('string', htmlspecialchars((string) $value));
        }

        return $xml->asXML();
    }

    protected function getTranslatedErrorMessage(string $errorCode): string
    {
        return __('vat-validator::messages.error_'.$errorCode) ?? "VAT ID invalid or error (Code: {$errorCode}).";
    }

    protected function parseXmlRpcResponse(string $xmlResponse): array
    {
        libxml_use_internal_errors(true);
        $sxe = simplexml_load_string(
            $xmlResponse,
            'SimpleXMLElement',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        if ($sxe === false) {
            Log::error('eVatR: Failed to parse outer XML response.', ['response_body' => $xmlResponse]);

            return ['valid' => false, 'data' => null, 'error' => __('vat-validator::messages.parse_error_outer'), 'error_code' => 'PARSE_ERROR_OUTER_INVALID_XML'];
        }

        if (isset($sxe->fault)) {
            $faultCode = 'UNKNOWN_FAULT_CODE';
            $faultString = 'Unknown XML-RPC fault.';
            if (isset($sxe->fault->value->struct)) {
                foreach ($sxe->fault->value->struct->member as $member) {
                    $name = (string) $member->name;
                    if ($name === 'faultCode') {
                        $faultCode = (string) $member->value->children()[0];
                    } elseif ($name === 'faultString') {
                        $faultString = (string) $member->value->children()[0];
                    }
                }
            }
            Log::warning('eVatR: Received XML-RPC Fault.', ['faultCode' => $faultCode, 'faultString' => $faultString, 'response_body' => $xmlResponse]);

            return ['valid' => false, 'data' => null, 'error' => __('vat-validator::messages.fault', ['message' => $faultString]), 'error_code' => "FAULT_{$faultCode}"];
        }

        $data = [];

        // Check for the nested XML string structure
        if (isset($sxe->params->param->value->string)) {
            $escapedInnerXml = (string) $sxe->params->param->value->string;
            $innerXmlString = html_entity_decode($escapedInnerXml);

            libxml_use_internal_errors(true);
            $innerSxe = simplexml_load_string(
                $innerXmlString,
                'SimpleXMLElement',
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );
            if ($innerSxe === false) {
                Log::error('eVatR: Failed to parse inner XML string.', ['decoded_inner_xml' => $innerXmlString, 'original_response' => $xmlResponse]);

                return ['valid' => false, 'data' => null, 'error' => 'Failed to parse API response (invalid inner XML).', 'error_code' => 'PARSE_ERROR_INNER_INVALID_XML'];
            }

            // The inner XML contains a list of <param> which are key-value pairs in an <array>
            if (isset($innerSxe->param)) { // Check if the root of inner XML is <params> containing <param>
                foreach ($innerSxe->param as $innerParam) {
                    if (isset($innerParam->value->array->data)) {
                        $values = [];
                        foreach ($innerParam->value->array->data->value as $valNode) {
                            if (isset($valNode->string)) {
                                $values[] = (string) $valNode->string;
                            } else {
                                // Handle other types if necessary, though BZSt seems to use string
                                $values[] = (string) $valNode->children()[0];
                            }
                        }
                        if (count($values) === 2) {
                            $data[$values[0]] = $values[1];
                        }
                    }
                }
            } else {
                Log::warning('eVatR: Inner XML does not have the expected <params><param> structure for key-value pairs.', ['decoded_inner_xml' => $innerXmlString]);
            }

        } else {
            Log::warning('eVatR: Response is not a fault and does not contain the expected nested XML string.', ['response_body' => $xmlResponse]);
        }

        if (empty($data)) {
            // This condition will be met if the parsing above fails to populate $data
            if (! isset($sxe->fault)) { // Avoid double-messaging if it was already a fault
                Log::error('eVatR: Could not extract data from XML response using known structures.', ['response_body' => $xmlResponse]);

                return ['valid' => false, 'data' => null, 'error' => 'Failed to parse API response (unknown final structure).', 'error_code' => 'PARSE_ERROR_UNKNOWN_FINAL_STRUCTURE'];
            }

            // If it was a fault, the fault handling already returned.
            // If it wasn't a fault, but $data is still empty, it's an issue.
            // Return an error if no data extracted and not a fault.
            return ['valid' => false, 'data' => null, 'error' => 'Failed to parse API response (no data extracted).', 'error_code' => 'PARSE_ERROR_NO_DATA_EXTRACTED'];

        }

        $errorCode = $data['ErrorCode'] ?? null;

        if ($errorCode === '200') {
            return ['valid' => true, 'data' => $data, 'error' => null, 'error_code' => $errorCode];
        } else {
            $errorMessage = $data['ErrorText'] ?? __('vat-validator::messages.generic_error');
            // Only map if ErrorText is missing AND there is an ErrorCode
            if (! isset($data['ErrorText']) && $errorCode !== null) {
                $errorMessage = $this->getTranslatedErrorMessage($errorCode);
            }

            return ['valid' => false, 'data' => $data, 'error' => $errorMessage, 'error_code' => $errorCode ?? 'PARSE_FAILURE'];
        }
    }
}
