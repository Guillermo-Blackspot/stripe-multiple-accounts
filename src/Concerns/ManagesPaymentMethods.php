<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use Stripe\Collection;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

/**
 * Manages the customer payment methods
 * 
 * Every method can throws InvalidStripeServiceIntegration|InvalidStripeCustomer
 * 
 * @method createStripeSetupIntent($serviceIntegrationId = null, $opts = [])
 * @method getStripePaymentMethods($serviceIntegrationId = null, $type = 'card')
 * @method addStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
 * @method deleteStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
 * @method getOrAddStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId, $type = 'card')
 * @method setDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId)
 * @method updateDefaultStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
 * @method getDefaultStripePaymentMethod($serviceIntegrationId = null)
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
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createStripeSetupIntent($serviceIntegrationId = null, $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->createStripeSetupIntent($opts);
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
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function getStripePaymentMethods($serviceIntegrationId = null, $type = 'card')
    {            
        return $this->asLocalStripeCustomer($serviceIntegrationId)->getStripePaymentMethods($type);        
    }

    /**
     * Add payment method to customer
     * 
     * Send data to stripe
     * 
     * @param int|null $serviceIntegrationId
     * @param string $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function addStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        return $this->asLocalStripeCustomer($serviceIntegrationId)->addStripePaymentMethod($paymentMethodId);
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
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function deleteStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {    
        $this->asLocalStripeCustomer($serviceIntegrationId)->deleteStripePaymentMethod($paymentMethodId);
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
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function getOrAddStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId, $type = 'card')
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->getOrAddStripePaymentMethod($paymentMethodId, $type);        
    }    

    /**
     * Update customer's default payment method.
     *
     * alias of updateDefaultStripePaymentMethod
     * 
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function setDefaultStripePaymentMethod($serviceIntegrationId, $paymentMethodId)
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->setDefaultStripePaymentMethod($paymentMethodId);
    }

    /**
     * Update customer's default payment method.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     *
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function updateDefaultStripePaymentMethod($serviceIntegrationId = null, $paymentMethodId)
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->setDefaultStripePaymentMethod($paymentMethodId);
    }

    /**
     * Get the default payment method for the customer.
     * 
     * From invoice_settings or legacy default_source
     *
     * @param int|null  $serviceIntegrationId
     * 
     * @return \Stripe\PaymentMethod|\Stripe\Card|\Stripe\BankAccount|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function getDefaultStripePaymentMethod($serviceIntegrationId = null)
    {        
        return $this->asLocalStripeCustomer($serviceIntegrationId)->getDefaultStripePaymentMethod();
    }

}