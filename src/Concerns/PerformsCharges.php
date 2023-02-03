<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * Performs charges
 */
trait PerformsCharges
{

    /**
     * Create a setup intent
     * 
     * @param int|null  $serviceIntegrationId
     * @param array  $opts
     * 
     * @throws \Stripe\Exception\ApiErrorException — if the request fails
     * @return \Stripe\SetupIntent|null
     */
    public static function createSetupIntent($serviceIntegrationId = null, array $opts = [])
    {            
        /** @var \Stripe\StripeClient */
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }        
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        $opts = array_merge($opts,[ 'customer' => $stripeCustomerId]);

        if (!isset($opts['payment_method_types'])) {
            $opts = array_merge($opts, ['payment_method_types' => ['card']]);
        }

        return $stripeClientConnection->setupIntents->create($opts);
    }
}