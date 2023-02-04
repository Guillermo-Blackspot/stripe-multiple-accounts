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
     * @param array  $paymentMethods ['card']
     * @param array  $opts
     * 
     * @return \Stripe\SetupIntent|null
     * @throws \Stripe\Exception\ApiErrorException — if the request fails
     */
    public function createStripeSetupIntent($serviceIntegrationId = null, $paymentMethods, $opts = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }        
        
        $stripeCustomerId = $this->getRelatedStripeCustomerId($serviceIntegrationId);

        if (is_null($stripeCustomerId)) {
            return ;
        }        

        $opts = array_merge(['payment_method_types' => $paymentMethods], $opts);
        
        $opts['customer'] = $stripeCustomerId;

        return $stripeClientConnection->setupIntents->create($opts);
    }

    /**
     * Create a payment intent
     * 
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $paymentMethods ['card','oxxo']
     * @param array  $opts
     * 
     * @return \Stripe\SetupIntent|null
     * @throws \Stripe\Exception\ApiErrorException — if the request fails
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
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param string  $paymentMethodId
     * @param array  $opts
     * 
     * @return \Stripe\PaymentIntent|null
     * @throws \Laravel\Cashier\Exceptions\IncompletePayment
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
     * Create a new Payment instance with a Stripe PaymentIntent.
     * @param int|null  $serviceIntegrationId
     * @param int  $amount
     * @param array  $options
     * @return \Laravel\Cashier\Payment
     */
    public function createStripePayment($serviceIntegrationId = null, $amount, array $options = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        $options = array_merge(['currency' => 'mxn'], $options);

        $options['amount'] = $amount;

        return $stripeClientConnection->paymentIntents->create($options);
    }


    
    /**
     * Refund a customer for a charge.
     *
     * @param int|null  $serviceIntegrationId
     * @param  string  $paymentIntent
     * @param  array  $options
     * @return \Stripe\Refund|null
     */
    public function stripeRefund($serviceIntegrationId = null, $paymentIntentId, array $options = [])
    {
        $stripeClientConnection = $this->getStripeClientConnection($serviceIntegrationId);

        if (is_null($stripeClientConnection)) {
            return ;
        }

        return $stripeClientConnection->refunds->create(
            ['payment_intent' => $paymentIntentId] + $options
        );
    }
}