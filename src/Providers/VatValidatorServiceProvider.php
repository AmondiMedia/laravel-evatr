<?php

namespace AmondiMedia\LaravelEvatr\Providers;

use AmondiMedia\LaravelEvatr\Http\Client as VatClient;
use AmondiMedia\LaravelEvatr\Rules\ValidVatNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class LaravelEvatrServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'vat-validator');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/vat_validator.php' => config_path('vat_validator.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../../resources/lang' => resource_path('lang/vendor/vat-validator'),
            ], 'vat-validator-translations');
        }

        Validator::extend('valid_vat_number', function ($attribute, $value, $parameters, $validator) {
            $countryCode = $parameters[0] ?? null;
            $vatNumberWithoutCountry = $parameters[1] ?? $value;

            if (count($parameters) >= 2) {
                $fullVatNumber = $countryCode.$vatNumberWithoutCountry;
            } else {
                $fullVatNumber = $value;
            }

            return (new ValidVatNumber($this->app->make(VatClient::class)))->passes($attribute, $fullVatNumber);
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/vat_validator.php',
            'vat_validator'
        );

        $this->app->singleton(VatClient::class, function ($app) {
            return new VatClient(
                config('vat_validator.evatr_api_url'),
                config('vat_validator.requester_vat_id'),
                config('vat_validator.timeout', 10)
            );
        });
    }
}
