<?php

namespace BlackSpot\StripeMultipleAccounts;

use Illuminate\Support\ServiceProvider;

class StripeMultipleAccountsServiceProvider extends ServiceProvider
{

    public const PACKAGE_NAME = 'stripe-multiple-accounts-service';

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerPublishables();
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/stripe-multiple-accounts.php', 'stripe-multiple-accounts');
    }

    protected function registerPublishables()
    {
        $this->publishes([
            __DIR__.'/../config/stripe-multiple-accounts.php' => base_path('config/stripe-multiple-accounts'),
        ], ['stripe-multiple-accounts', 'stripe-multiple-accounts:config']);
    }
}
