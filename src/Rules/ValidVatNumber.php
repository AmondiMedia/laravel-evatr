<?php

namespace AmondiMedia\LaravelEvatr\Rules;

use AmondiMedia\LaravelEvatr\Http\Client as VatClient;
use Illuminate\Contracts\Validation\Rule;

class ValidVatNumber implements Rule
{
    protected VatClient $client;

    protected ?string $errorMessage = null;

    protected ?string $errorCode = null;

    // Optional parameters for qualified validation
    protected ?string $companyName;

    protected ?string $city;

    protected ?string $zipCode;

    protected ?string $street;

    /**
     * Create a new rule instance.
     * The VatClient will be injected by the service provider or resolved from the container.
     * Additional parameters can be used for "qualified confirmation request".
     */
    public function __construct(
        VatClient $client,
        ?string $companyName = null,
        ?string $city = null,
        ?string $zipCode = null,
        ?string $street = null
    ) {
        $this->client = $client;
        $this->companyName = $companyName;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->street = $street;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value  The VAT number to validate (e.g., "DE123456789")
     */
    public function passes($attribute, $value): bool
    {
        if (! is_string($value) || empty($value)) {
            $this->errorMessage = 'The VAT number must be a non-empty string.';

            return false;
        }

        $result = $this->client->validate(
            $value, // This is UstId_2
            $this->companyName,
            $this->city,
            $this->zipCode,
            $this->street
        );

        if (! $result['valid']) {
            $this->errorMessage = $result['error'] ?: 'The VAT number is not valid.';
            $this->errorCode = $result['error_code'] ?? 'UNKNOWN';

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?: 'The provided VAT number is invalid or could not be verified.';
    }

    /**
     * Get the API error code if available.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }
}
