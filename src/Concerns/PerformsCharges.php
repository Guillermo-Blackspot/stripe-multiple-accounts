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
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }

        $opts = array_merge([
            'confirmation_method' => 'automatic',
            'confirm'             => true,
        ], $opts);

        $opts['customer']       = $stripeCustomerId;
        $opts['payment_method'] = $paymentMethodId;

        return $this->createStripePayment($serviceIntegrationId, $amount, $opts);
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
    public function stripePayWith($serviceIntegrationId = null, $amount, array $paymentMethods, array $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }        
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }
        
        $opts['customer']             = $stripeCustomerId;
        $opts['payment_method_types'] = $paymentMethods;
        $opts['amount']               = $amount;
        $opts['currency']             = 'mxn';

        unset($opts['automatic_payment_methods']);

        return $stripeClientConnection->paymentIntents->create($opts);
    }

    /**
     * Create a new Payment instance with a Stripe PaymentIntent.
     * 
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     */
    public function createStripePayment($serviceIntegrationId = null, $amount, array $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $opts = array_merge(['currency' => 'mxn'], $opts);

        $opts['amount'] = $amount;

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
    public function stripeRefund($serviceIntegrationId = null, $paymentIntentId, array $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        return $stripeClientConnection->refunds->create(array_merge(
            ['payment_intent' => $paymentIntentId], $opts
        ));
    }    
    
}