<?php 

namespace BlackSpot\StripeMultipleAccounts\Services\Traits;
use Illuminate\Database\Eloquent\Model;

/**
 * Performs charges
 * 
 * @method public function findPaymentIntent(Model $billable, $paymentIntentId)
 * @method public function makeCharge(Model $billable, $amount, $paymentMethodId, array $opts = [])
 * @method public function pay(Model $billable, $amount, array $opts = [])
 * @method public function createPaymentIntentWith(Model $billable, $amount, array $paymentMethods, array $opts = [])
 * @method public function createPaymentIntent(Model $billable, $amount, array $opts = [])
 * @method public function refundPaymentIntent(Model $billable, $paymentIntentId, array $opts = [])
 */
trait PerformCharges
{    
    /**
     * Find a payment intent by ID.
     *
     * @param  Model  $billable
     * @param  string  $paymentIntentId
     * @return \Stripe\PaymentIntent|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function findPaymentIntent(Model $billable, $paymentIntentId)
    {
        return $this->getCustomer($billable)->findPaymentIntent($paymentIntentId);
    }

    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param Model $billable
     * @param int  $amount
     * @param string  $paymentMethodId
     * @param array  $opts
     * @return \Stripe\PaymentIntent|null
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function makeCharge(Model $billable, $amount, $paymentMethodId, array $opts = [])
    { 
        return $this->getCustomer($billable)->makeCharge($amount, $paymentMethodId, $opts);
    }

    /**
     * Create a new PaymentIntent instance.
     *
     * automatic_payment_methods is enabled
     * 
     * @param  Model  $billable
     * @param  int  $amount
     * @param  array  $opts
     * @return \Stripe\PaymentIntent|null
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function pay(Model $billable, $amount, array $opts = [])
    {
        return $this->getCustomer($billable)->pay($amount, $opts);
    }
    
    /**
     * Create a new PaymentIntent instance for the given payment method types.
     * 
     * @param Model $billable
     * @param int  $amount
     * @param array  $paymentMethods ['card','oxxo']
     * @param array  $opts
     * @return \Stripe\PaymentIntent|null
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createPaymentIntentWith(Model $billable, $amount, array $paymentMethods, array $opts = [])
    {
        return $this->getCustomer($billable)->createPaymentIntentWith($amount, $paymentMethods, $opts);
    }

    /**
     * Create a new payment intent
     * 
     * @param Model $billable
     * @param int  $amount
     * @param array  $opts
     * @return \Stripe\PaymentIntent|null
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function createPaymentIntent(Model $billable, $amount, array $opts = [])
    {
        return $this->getCustomer($billable)->createPaymentIntent($amount, $opts);
    }

    /**
     * Refund a customer for a charge.
     *
     * @param Model $billable
     * @param  string  $paymentIntent
     * @param  array  $opts
     * @return \Stripe\Refund|null
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function refundPaymentIntent(Model $billable, $paymentIntentId, array $opts = [])
    {
        return $this->getCustomer($billable)->refundPaymentIntent($paymentIntentId, $opts);
    }        
}