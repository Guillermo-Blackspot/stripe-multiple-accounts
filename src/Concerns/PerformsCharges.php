<?php 

namespace BlackSpot\StripeMultipleAccounts\Concerns;

/**
 * Performs charges
 */
trait PerformsCharges
{    
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param string  $paymentMethodId
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     */
    public function makeStripeCharge($serviceIntegrationId = null, $amount, $paymentMethodId, array $opts = [])
    { 
        $opts = array_merge([
            'confirmation_method' => 'automatic',
            'confirm'             => true,
        ], $opts);
        
        $opts['payment_method'] = $paymentMethodId;

        return $this->createStripePaymentIntent($serviceIntegrationId, $amount, $opts);
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
     */
    public function stripePay($serviceIntegrationId = null, $amount, array $opts = [])
    {
        $opts['automatic_payment_methods'] = ['enabled' => true];

        unset($opts['payment_method_types']);

        return $this->createStripePaymentIntent($serviceIntegrationId, $amount, $opts);
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
     */
    public function createStripePaymentIntentWith($serviceIntegrationId = null, $amount, array $paymentMethods, array $opts = [])
    {                
        $opts['payment_method_types'] = $paymentMethods;        

        unset($opts['automatic_payment_methods']);

        return $this->createStripePaymentIntent($serviceIntegrationId, $amount, $opts);
    }

    /**
     * Create a new payment intent
     * 
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     */
    public function createStripePaymentIntent($serviceIntegrationId = null, $amount, array $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $stripeCustomerId = $this->getStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        $opts = array_merge(['currency' => 'mxn'], $opts);

        $opts['amount']   = $amount;
        $opts['customer'] = $stripeCustomerId;

        return $stripeClientConnection->paymentIntents->create($opts);
    }

    /**
     * Refund a customer for a charge.
     *
     * @param int|null  $serviceIntegrationId
     * @param  string  $paymentIntent
     * @param  array  $opts
     * 
     * @return \Stripe\Refund|null
     */
    public function refundStripePaymentIntent($serviceIntegrationId = null, $paymentIntentId, array $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        return $stripeClientConnection->refunds->create(array_merge(
            ['payment_intent' => $paymentIntentId], $opts
        ));
    }    

    /**
     * Find a payment intent by ID.
     *
     * @param  int|null  $serviceIntegrationId
     * @param  string  $paymentIntentId
     * @return \Stripe\PaymentIntent|null
     */
    public function findStripePaymentIntent($serviceIntegrationId = null, $paymentIntentId)
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        return $stripeClientConnection->paymentIntents->retrieve($paymentIntentId);
    }
    
}