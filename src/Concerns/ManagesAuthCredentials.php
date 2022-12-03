<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * Manages the auth credentials to connect with stripe
 * 
 * @method getRelatedStripeSecretKey($serviceIntegrationId = null)
 */
trait ManagesAuthCredentials
{
    /**
     * Get the related stripe secret key
     * 
     * @param int|null 
     * @return string|null
     */
    public function getRelatedStripeSecretKey($serviceIntegrationId = null)
    {
        $payloadColumn     = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');
        $stripeSecret      = config('stripe-multiple-accounts.stripe_integrations.payload.stripe_secret', 'stripe_secret');
        $stripeIntegration = $this->resolveStripeServiceIntegration($serviceIntegrationId);

        if (is_null($stripeIntegration)) {
            return ;
        }

        if (!isset($stripeIntegration->{$payloadColumn})) {
            return ;
        }

        return json_decode($stripeIntegration->{$payloadColumn}, true)[$stripeSecret];
    }

    /**
     * Get the related stripe key
     * 
     * @param int|null
     * @return string|null
     */
    public function getRelatedStripePublicKey($serviceIntegrationId = null)
    {
        $payloadColumn    = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');
        $stripePublicKey  = config('stripe-multiple-accounts.stripe_integrations.payload.stripe_key', 'stripe_key');
        $stripeIntegration = $this->resolveStripeServiceIntegration($serviceIntegrationId);

        if (is_null($stripeIntegration)) {
            return ;
        }

        if (!isset($stripeIntegration->{$payloadColumn})) {
            return ;
        }

        return json_decode($stripeIntegration->{$payloadColumn}, true)[$stripePublicKey];
    }
}
