<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * Performs charges
 */
trait PerformsCharges
{    
    /**
     * Find a payment intent by ID.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentIntentId
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function findStripePaymentIntent($serviceIntegrationId = null, $paymentIntentId)
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->findStripePaymentIntent($paymentIntentId);
    }

    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param string  $paymentMethodId
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function makeStripeCharge($serviceIntegrationId = null, $amount, $paymentMethodId, array $opts = [])
    { 
        return $this->asLocalStripeCustomer($serviceIntegrationId)->makeStripeCharge($amount, $paymentIntentId, $opts);
    }

    /**
     * Create a new PaymentIntent instance.
     *
     * automatic_payment_methods is enabled
     * 
     * @param  int|null  $serviceIntegrationId
     * @param  int  $amount
     * @param  array  $opts
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function stripePay($serviceIntegrationId = null, $amount, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->stripePay($amount, $opts);
    }
    
    /**
     * Create a new PaymentIntent instance for the given payment method types.
     * 
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $paymentMethods ['card','oxxo']
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createStripePaymentIntentWith($serviceIntegrationId = null, $amount, array $paymentMethods, array $opts = [])
    {                
        return $this->asLocalStripeCustomer($serviceIntegrationId)->stripePay($amount, $paymentMethods, $opts);
    }

    /**
     * Create a new payment intent
     * 
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createStripePaymentIntent($serviceIntegrationId = null, $amount, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->createStripePaymentIntent($amount, $opts);
    }

    /**
     * Refund a customer for a charge.
     *
     * @param int|null  $serviceIntegrationId
     * @param  string  $paymentIntent
     * @param  array  $opts
     * 
     * @return \Stripe\Refund|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function refundStripePaymentIntent($serviceIntegrationId = null, $paymentIntentId, array $opts = [])
    {
        return $this->asLocalStripeCustomer($serviceIntegrationId)->refundStripePaymentIntent($paymentIntentId, $opts);
    }        
}