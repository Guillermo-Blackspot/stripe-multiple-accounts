<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use Stripe\Collection;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

/**
 * Manages the customer payment methods
 * 
 * @method getStripePaymentMethods(?int $serviceIntegrationId = null, string $type = 'card') : \Stripe\Collection
 * @method addStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId) : \Stripe\PaymentMethod
 * @method deleteStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId) : void
 * @method getOrAddStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId, string $type = 'card') : \Stripe\PaymentMethod|null
 */
trait ManagesPaymentMethods
{
    
    /**
     * Get the related stripe customer payment methods
     * 
     * Fetch from stripe
     * 
     * @param int|null  $serviceIntegrationId
     * @param string  $type = 'card'
     * 
     * @return \Stripe\Collection<\Stripe\PaymentMethod/>
     */
    public function getStripePaymentMethods($serviceIntegrationId = null, $type = 'card')
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

        return $stripeClientConnection->customers->allPaymentMethods($stripeCustomerId, ['type' => $type]);
    }

    /**
     * Add payment method to customer
     * 
     * Send data to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string $paymentMethodId
     * @return \Stripe\PaymentMethod
     */
    public function addStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        return $stripeClientConnection->paymentMethods->attach($paymentMethodId, ['customer' => $stripeCustomerId]);
    }
 
    /**
     * Delete payment method from the customer
     * 
     * Send data to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string $paymentMethodId
     * 
     * @return void
     */
    public function deleteStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        $stripeClientConnection->paymentMethods->detach($paymentMethodId);
    }


    /**
     * Get or add payment method to customer
     * 
     * Send data to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string $paymentMethodId
     * @param string $type = 'card'
     * 
     * @return \Stripe\PaymentMethod|null
     */
    public function getOrAddStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId, $type = 'card')
    {
        $stripePaymentMethods = collect($this->getStripePaymentMethods($serviceIntegrationId, $type)->data);        
        $stripePaymentMethod  = $stripePaymentMethods->firstWhere('id', $paymentMethodId);

        if ($stripePaymentMethod instanceof PaymentMethod) {
            return $stripePaymentMethod;
        }

        return $this->addStripePaymentMethod($serviceIntegrationId, $paymentMethodId);
    }    
}