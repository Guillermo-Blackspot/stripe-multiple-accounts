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
        return optional(optional($this->resolveStripeServiceIntegration($serviceIntegrationId))->payload)['stripe_secret'];
    }
}
