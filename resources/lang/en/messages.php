<?php

return [
    'generic_error' => 'VAT ID invalid or error during validation.',
    'parse_error_outer' => 'Failed to parse API response (invalid outer XML).',
    'fault' => 'API Fault: :message',
    'error_200' => 'The requested VAT ID number is valid.',
    'error_201' => 'The requested VAT ID number is invalid.',
    'error_202' => 'The requested VAT ID number is invalid. It is not registered in the business register of the relevant EU member state.',
    'error_203' => 'The requested VAT ID number is invalid. It is only valid from the date specified in the Valid from field.',
    'error_204' => 'The requested VAT ID number is invalid. It was valid from the date specified in the Valid from field to the Valid until field.',
    'error_205' => 'Your request cannot currently be answered by the requested EU member state or for other reasons. Please try again later.',
    'error_206' => 'Your German VAT ID number is invalid. Therefore, a confirmation request is not possible.',
    'error_208' => 'Another user is currently processing the VAT ID number you requested. Please try again later.',
    'error_209' => 'The requested VAT ID number is invalid. It does not correspond to the structure applicable to this EU member state.',
    'error_210' => 'The requested VAT ID number is invalid. It does not comply with the check digit rules applicable to this EU member state.',
    'error_211' => 'The requested VAT ID number is invalid. It contains invalid characters (such as spaces, periods, hyphens, etc.).',
    'error_212' => 'The requested VAT ID number is invalid. It contains an invalid country code.',
    'error_213' => 'You are not authorized to request a German VAT ID number.',
    'error_214' => 'Your German VAT ID number is incorrect. It begins with \'DE\' followed by nine digits.',
    'error_215' => 'Your request does not contain all the information required for a simple confirmation request (your German VAT ID number and your foreign VAT ID number).',
    'error_216' => 'Your request does not contain all the information required for a qualified confirmation request. A simple confirmation request was performed with the following result: The requested VAT ID number is valid.',
    'error_217' => 'An error occurred while processing the data from the requested EU member state. Your request cannot therefore be processed.',
    'error_218' => 'A qualified confirmation is currently not possible. A simple confirmation request was made with the following result: The requested VAT ID number is valid.',
    'error_219' => 'An error occurred while processing the qualified confirmation request. A simple confirmation request was performed with the following result: The requested VAT ID number is valid.',
    'error_221' => 'The request data does not contain all required parameters or is of an invalid data type.',
    'error_223' => 'The requested VAT ID number is valid. The print function is no longer available because the proof must be provided in accordance with Section 18e.1 of the VAT AE.',
    'error_999' => 'We are currently unable to process your request. Please try again later.',
];
