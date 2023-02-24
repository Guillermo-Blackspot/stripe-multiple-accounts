<?php

namespace BlackSpot\StripeMultipleAccounts\Exceptions;

use BlackSpot\StripeMultipleAccounts\PaymentIntentHelper;
use Exception;
use Throwable;

class IncompleteStripePayment extends Exception
{
    /**
     * The Cashier Payment object.
     *
     * @var \BlackSpot\StripeMultipleAccounts\PaymentIntentHelper
     */
    public $payment;

    /**
     * Create a new IncompletePayment instance.
     *
     * @param  \BlackSpot\StripeMultipleAccounts\PaymentIntentHelper  $payment
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(PaymentIntentHelper $payment, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->payment = $payment;
    }

    /**
     * Create a new IncompletePayment instance with a `payment_action_required` type.
     *
     * @param  \BlackSpot\StripeMultipleAccounts\PaymentIntentHelper  $payment
     * @return static
     */
    public static function paymentMethodRequired(PaymentIntentHelper $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because of an invalid payment method.'
        );
    }

    /**
     * Create a new IncompletePayment instance with a `requires_action` type.
     *
     * @param  \BlackSpot\StripeMultipleAccounts\PaymentIntentHelper  $payment
     * @return static
     */
    public static function requiresAction(PaymentIntentHelper $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because additional action is required before it can be completed.'
        );
    }

    /**
     * Create a new IncompletePayment instance with a `requires_confirmation` type.
     *
     * @param  \BlackSpot\StripeMultipleAccounts\PaymentIntentHelper  $payment
     * @return static
     */
    public static function requiresConfirmation(PaymentIntentHelper $payment)
    {
        return new static(
            $payment,
            'The payment attempt failed because it needs to be confirmed before it can be completed.'
        );
    }
}