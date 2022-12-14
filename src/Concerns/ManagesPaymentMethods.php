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
     * Add stripe payment method resource
     *  
     * Send to stripe
     * 
     * @param int|string|null $serviceIntegrationId or you can pass directly the $tokenId instead it
     * @param string|null $tokenId
     * @param array $opts
     * @throws \Stripe\Exception\ApiErrorException
     * @return \Stripe\BankAccount|\Stripe\Card|\Stripe\Source
     */
    public function attachStripeCustomerPaymentMethodResource($serviceIntegrationId = null, $tokenId = null, $opts = [])
    {
        if (!is_numeric($serviceIntegrationId)) {
            $tokenId = $serviceIntegrationId;
            $serviceIntegrationId = null;
        }

        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);

        if ($stripeSecretKey == null) {
            return null;
        }

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if ($stripeCustomerId == null) {
            return null;
        }

        $opts['source'] = $tokenId;
    
        return (new StripeClient($stripeSecretKey))->customers->createSource($stripeCustomerId, $opts);
    }

    /**
     * Get the related stripe customer payment methods
     * 
     * Fetch from stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param array $opts
     * @return \Stripe\Collection
     */
    public function getRelatedStripeCustomerPaymentMethods($serviceIntegrationId = null, $opts = ['type' => 'card'])
    {        
        $stripeSecretKey       = $this->getRelatedStripeSecretKey($serviceIntegrationId);
        $emptyStripeCollection = new Collection();
        $emptyStripeCollection->data = [];

        if (is_null($stripeSecretKey)) {
            return $emptyStripeCollection;
        }        

        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return $emptyStripeCollection;
        }

        return (new StripeClient($stripeSecretKey))->customers->allPaymentMethods($stripeCustomerId, $opts);
    }

    /**
     * Detach payment method from stripe customer payment methods
     * 
     * Send to stripe
     * 
     * @param int|string|null $serviceIntegrationId or you can pass directly the $paymentMethodId instead it
     * @param string|null $paymentMethodId
     * @param array $opts
     * @return \Stripe\PaymentMethod|null
     */
    public function detachStripeCustomerPaymentMethodResource($serviceIntegrationId = null, $paymentMethodId = null, $opts = [])
    {
        if (!is_numeric($serviceIntegrationId)) {
            $paymentMethodId = $serviceIntegrationId;
            $serviceIntegrationId = null;
        }

        $stripeSecretKey = $this->getRelatedStripeSecretKey($serviceIntegrationId);

        if ($stripeSecretKey == null) {
            return null;
        }

        return (new StripeClient($stripeSecretKey))->paymentMethods->detach($paymentMethodId, null, $opts);
    }
}