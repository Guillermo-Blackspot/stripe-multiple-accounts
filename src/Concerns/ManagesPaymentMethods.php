<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use Stripe\Collection;
use Stripe\StripeClient;

/**
 * Manages the customer payment methods
 * 
 * @method createStripePaymentMethodResource($serviceIntegrationId = null, $tokenId = null, $opts = [])
 */
trait ManagesPaymentMethods
{
    
    /**
     * Get the related stripe customer payment methods
     * 
     * Fetch from stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param array $opts
     * @return \Stripe\Collection<\Stripe\PaymentMethod/>
     */
    public function getStripeCustomerPaymentMethods($serviceIntegrationId = null, $type = 'card', $opts = [])
    {    
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);
        $emptyStripeCollection  = new Collection();
        $emptyStripeCollection->data = [];

        if (is_null($stripeClientConnection)) {
            return $emptyStripeCollection;
        }
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return $emptyStripeCollection;
        }

        return $stripeClientConnection->paymentMethods->all(
            array_merge(['customer' => $stripeCustomerId, 'type' => $type], $opts)
        );    
    }


    
}