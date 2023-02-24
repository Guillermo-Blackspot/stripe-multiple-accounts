<?php

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscription as Subscription;
use BlackSpot\StripeMultipleAccounts\PaymentIntentHelper;
use Stripe\Exception\CardException as StripeCardException;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\PaymentIntent as StripePaymentIntent;

trait HandlesPaymentFailures
{
    /**
     * Handle a failed payment for the given subscription.
     *
     * @param  int  $serviceIntegrationId
     * @param  \BlackSpot\StripeMultipleAccounts\Models\StripeSusbcription  $subscription
     * @param  \Stripe\PaymentMethod|string|null  $paymentMethod
     * @return void
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment
     *
     * @internal
     */
    public function handlePaymentFailure($serviceIntegrationId, Subscription $subscription, $paymentMethodId = null)
    {

        // No requires validations
        if (!$subscription->hasIncompletePayment()) {
            return ;
        }

        try {
            (new PaymentIntentHelper($serviceIntegrationId, $subscription->latestPaymentIntent()))->validate();
        } catch (IncompleteStripePayment $e) {

            if (! $e->payment->requiresConfirmation()) {
                throw $e;
            }

            try {                    
                $paymentIntent = ($paymentMethodId)
                                    ? $e->payment->confirm(['expand' => ['invoice.subscription'],'payment_method' => $paymentMethodId])
                                    : $e->payment->confirm(['expand' => ['invoice.subscription']]);

            } catch (StripeCardException) {
                $paymentIntent = $e->payment->asStripePaymentIntent(['invoice.subscription']);
            }

            $subscription->fill(['status' => $paymentIntent->invoice->subscription->status])->save();

            if ($subscription->hasIncompletePayment()) {
                (new PaymentIntentHelper($serviceIntegrationId, $paymentIntent))->validate();
            }
        }
    }
}