<?php 

namespace BlackSpot\StripeMultipleAccounts\Services\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Manages the customer payment methods
 * 
 * Every method can throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
 * 
 * @method public function createSetupIntent(Model $billable, $opts = [])
 * @method public function getPaymentMethods(Model $billable, $type = 'card')
 * @method public function addPaymentMethod(Model $billable, $paymentMethodId)
 * @method public function deletePaymentMethod(Model $billable, $paymentMethodId)
 * @method public function getOrAddPaymentMethod(Model $billable, $paymentMethodId, $type = 'card')
 * @method public function setDefaultPaymentMethod(Model $billable, $paymentMethodId)
 * @method public function getDefaultPaymentMethod(Model $billable)
 */
trait ManagePaymentMethods
{
    /**
     * Create a setup intent
     * 
     * @param Model  $billable
     * @param array  $opts
     * @return \Stripe\SetupIntent|null
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function createSetupIntent(Model $billable, $opts = [])
    {
        return $this->getCustomer($billable)->createSetupIntent($opts);
    }

    /**
     * Get the related stripe customer payment methods
     * 
     * @param Model  $billable
     * @param string  $type = 'card'
     * @return \Stripe\Collection<\Stripe\PaymentMethod/>
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function getPaymentMethods(Model $billable, $type = 'card')
    {
        return $this->getCustomer($billable)->getStripePaymentMethods($type);        
    }

    /**
     * Add payment method to customer
     * 
     * @param Model $billable
     * @param string $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function addPaymentMethod(Model $billable, $paymentMethodId)
    {
        return $this->getCustomer($billable)->addStripePaymentMethod($paymentMethodId);
    }
 
    /**
     * Delete payment method from the customer
     * 
     * @param Model $billable
     * @param string $paymentMethodId
     * @return void|null
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function deletePaymentMethod(Model $billable, $paymentMethodId)
    {
        $this->getCustomer($billable)->deletePaymentMethod($paymentMethodId);
    }


    /**
     * Get or add payment method to customer
     * 
     * @param Model $billable
     * @param string $paymentMethodId
     * @param string $type = 'card'
     * @return \Stripe\PaymentMethod|null
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function getOrAddPaymentMethod(Model $billable, $paymentMethodId, $type = 'card')
    {
        return $this->getCustomer($billable)->getOrAddPaymentMethod($paymentMethodId, $type);        
    }    

    /**
     * Update customer's default payment method.
     * 
     * @param  Model  $billable
     * @param  string  $paymentMethodId
     * @return \Stripe\PaymentMethod|null
     * 
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function setDefaultPaymentMethod(Model $billable, $paymentMethodId)
    {
        return $this->getCustomer($billable)->setDefaultPaymentMethod($paymentMethodId);
    }

    /**
     * Get the default payment method for the customer.
     * 
     * From invoice_settings or legacy default_source
     *
     * @param Model  $billable
     * @return \Stripe\PaymentMethod|\Stripe\Card|\Stripe\BankAccount|null
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function getDefaultPaymentMethod(Model $billable)
    {
        return $this->getCustomer($billable)->getDefaultPaymentMethod();
    }

}