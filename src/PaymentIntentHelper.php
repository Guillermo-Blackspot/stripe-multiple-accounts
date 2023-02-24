<?php

namespace BlackSpot\StripeMultipleAccounts;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use \BlackSpot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment;
use Stripe\PaymentIntent as StripePaymentIntent;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;

class PaymentIntentHelper implements Arrayable, Jsonable, JsonSerializable
{
    use ForwardsCalls;
    use ManagesAuthCredentials;

    /**
     * The Stripe PaymentIntent instance.
     *
     * @var \Stripe\PaymentIntent
     */
    protected $paymentIntent;

    /**
     * The related service integration id
     *
     * @var int
     */
    protected $serviceIntegrationId;

    /**
     * Create a new Payment instance.
     * 
     * @param int  $serviceIntegrationId
     * @param  \Stripe\PaymentIntent  $paymentIntent
     * @return void
     */
    public function __construct($serviceIntegrationId, StripePaymentIntent $paymentIntent)
    {
        $this->serviceIntegrationId = $serviceIntegrationId;
        $this->paymentIntent        = $paymentIntent;
    }

    /**
     * Determine if the payment needs a valid payment method.
     *
     * @return bool
     */
    public function requiresPaymentMethod()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD;
    }

    /**
     * Determine if the payment needs an extra action like 3D Secure.
     *
     * @return bool
     */
    public function requiresAction()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_ACTION;
    }

    /**
     * Determine if the payment needs to be confirmed.
     *
     * @return bool
     */
    public function requiresConfirmation()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_CONFIRMATION;
    }

    /**
     * Determine if the payment needs to be captured.
     *
     * @return bool
     */
    public function requiresCapture()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_REQUIRES_CAPTURE;
    }

    /**
     * Determine if the payment was canceled.
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_CANCELED;
    }

    /**
     * Determine if the payment was successful.
     *
     * @return bool
     */
    public function isSucceeded()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_SUCCEEDED;
    }

    /**
     * Determine if the payment is processing.
     *
     * @return bool
     */
    public function isProcessing()
    {
        return $this->paymentIntent->status === StripePaymentIntent::STATUS_PROCESSING;
    }

    /**
     * Validate if the payment intent was successful and throw an exception if not.
     *
     * @return void
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment
     */
    public function validate()
    {
        if ($this->requiresPaymentMethod()) {
            throw IncompleteStripePayment::paymentMethodRequired($this);
        } elseif ($this->requiresAction()) {
            throw IncompleteStripePayment::requiresAction($this);
        } elseif ($this->requiresConfirmation()) {
            throw IncompleteStripePayment::requiresConfirmation($this);
        }
    }


    /**
     * The Stripe PaymentIntent instance.
     *
     * @param  array  $expand
     * @return \Stripe\PaymentIntent
     */
    public function asStripePaymentIntent(array $expand = [])
    {
        if ($expand) {            
            return $this->getStripeClientConnection($this->serviceIntegrationId)->paymentIntents->retrieve(
                $this->paymentIntent->id, ['expand' => $expand]
            );
        }

        return $this->paymentIntent;
    }

    public function confirm($opts = [])
    {
        return $this->getStripeClientConnection($this->serviceIntegrationId)->paymentIntents->confirm($this->paymentIntent->id, $opts);
    }


    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->asStripePaymentIntent()->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Dynamically get values from the Stripe object.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->paymentIntent->{$key};
    }

    /**
     * Dynamically pass missing methods to the PaymentIntent instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->paymentIntent, $method, $parameters);
    }
}