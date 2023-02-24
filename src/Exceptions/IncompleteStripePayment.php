<?php

namespace BlackSpot\StripeMultipleAccounts\Exceptions;

use Exception;
use Stripe\PaymentIntent;
use Throwable;

class IncompleteStripePayment extends Exception
{
    /**
     * The Cashier Payment object.
     *
     * @var \Stripe\PaymentIntent
     */
    public $payment;

    /**
     * Create a new IncompletePayment instance.
     *
     * @param  \Stripe\PaymentIntent  $payment
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(PaymentIntent $payment, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->payment = $payment;
    }

    /**
     * Create a new IncompletePayment instance with a `payment_action_required` type.
     *
     * @param  \Stripe\PaymentIntent  $payment
     * @return static
     */
    public static function paymentMethodRequired(PaymentIntent $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because of an invalid payment method.'
        );
    }

    /**
     * Create a new IncompletePayment instance with a `requires_action` type.
     *
     * @param  \Stripe\PaymentIntent  $payment
     * @return static
     */
    public static function requiresAction(PaymentIntent $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because additional action is required before it can be completed.'
        );
    }

    /**
     * Create a new IncompletePayment instance with a `requires_confirmation` type.
     *
     * @param  \Stripe\PaymentIntent  $payment
     * @return static
     */
    public static function requiresConfirmation(PaymentIntent $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because it needs to be confirmed before it can be completed.'
        );
    }
}