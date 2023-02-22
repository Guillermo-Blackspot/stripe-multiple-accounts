<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use Stripe\Collection;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

/**
 * Manages the customer payment methods
 * 
 * @method createStripeSetupIntent(?int $serviceIntegrationId = null, array $opts = []) : \Stripe\SetupIntent
 * @method getStripePaymentMethods(?int $serviceIntegrationId = null, string $type = 'card') : \Stripe\Collection
 * @method addStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId) : \Stripe\PaymentMethod
 * @method deleteStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId) : void
 * @method getOrAddStripePaymentMethod(?int $serviceIntegrationId = null, string $paymentMethodId, string $type = 'card') : \Stripe\PaymentMethod|null
 */
trait ManagesPaymentMethods
{
    
    /**
     * Create a setup intent
     * 
     * @param int|null  $serviceIntegrationId
     * @param array  $opts
     * 
     * @return \Stripe\SetupIntent|null
     * @throws \Stripe\Exception\ApiErrorException — if the request fails
     */
    public function createStripeSetupIntent($serviceIntegrationId = null, $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }        
        
        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }
        
        // Default payment_method_types = ['card']
        $opts['customer'] = $stripeCustomerId;

        return $stripeClientConnection->setupIntents->create($opts);
    }

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
        
        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

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
     * @return \Stripe\PaymentMethod|null
     */
    public function addStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

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
     * @return void|null
     */
    public function deleteStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

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

    /**
     * Update customer's default payment method.
     *
     * alias of updateDefaultStripePaymentMethod
     * 
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     */
    public function setDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId)
    {
        return $this->updateDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId);
    }

    /**
     * Update customer's default payment method.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     */
    public function updateDefaultStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }            
        
        $paymentMethod = $this->getOrAddStripePaymentMethod($serviceIntegrationId, $paymentMethodId);

        if (is_null($paymentMethod)) {
            return ;
        }

        $this->updateStripeCustomer($serviceIntegrationId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethod->id],
        ]);

        return $paymentMethod;
    }


    /**
     * Get the default payment method for the customer.
     * 
     * From invoice_settings or legacy default_source
     *
     * @param int|null  $serviceIntegrationId
     * 
     * @return \Stripe\PaymentMethod|\Stripe\Card|\Stripe\BankAccount|null
     */
    public function getDefaultStripePaymentMethod($serviceIntegrationId = null)
    {        
        $customer = $this->getStripeCustomer($serviceIntegrationId);

        if (is_null($customer)) {
            return ;
        }

        if ($customer->invoice_settings->default_payment_method) {
            return $customer->invoice_settings->default_payment_method;
        }

        // If we can't find a payment method, try to return a legacy source...
        return $customer->default_source;
    }

}