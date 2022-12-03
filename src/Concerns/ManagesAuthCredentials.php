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
        $payloadColumn = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');
        $stripeSecret  = config('stripe-multiple-accounts.stripe_integrations.payload.stripe_secret', 'stripe_secret');
        $payload       = optional(optional($this->resolveStripeServiceIntegration($serviceIntegrationId))->{$payloadColumn});

        return $payload instanceof \Illuminate\Support\Optional || $payload == null ? null : json_decode($payload)[$stripeSecret];
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
        $payload          = optional(optional($this->resolveStripeServiceIntegration($serviceIntegrationId))->{$payloadColumn});

        return $payload instanceof \Illuminate\Support\Optional || $payload == null ? null : json_decode($payload)[$stripePublicKey];
    }
}
