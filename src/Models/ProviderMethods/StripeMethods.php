<?php

namespace BlackSpot\StripeMultipleAccounts\Models\ProviderMethods;

class StripeMethods
{
    /**
     * Local memory cache
     *
     * @var \Stripe\Subscription
     */
    protected $recentlyStripeSubscrtionFetched = null;

    /**
     * Helpers
     */
    public static function resolveStripeStatusDescription($status)
    {
        switch ($status) {
            case 'incomplete':         return 'Primer cobro falló';                           break;
            case 'incomplete_expired': return 'Primer cobro falló y ya no puede reactivarse'; break;
            case 'trialing':           return 'En periodo de prueba';                         break;
            case 'active':             return 'Activo';                                       break;
            case 'past_due':           return 'La renovación falló';                          break;
            case 'canceled':           return 'Cancelado'; break;
            case 'unpaid':             return 'No pagado, acumulando facturas';               break;
            default:                   return 'Desconocido';                                  break;
        }
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function stripeOnStripeTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }


    /**
     * Determine if the subscription has a specific price.
     *
     * @param  string  $price
     * @return bool
     */
    public function stripeHasPrice($priceId)
    {
        return $this->service_integration_subscription_items->contains(function ($item) use ($priceId) {
            return $item->price_id === $priceId;
        });
    }

    /**
     * Determine if the subscription has multiple prices.
     *
     * @return bool
     */
    public function stripeHasMultiplePrices()
    {
        return $this->service_integration_subscription_items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function stripeHasSinglePrice()
    {
        return !$this->stripeHasMultiplePrices();
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function stripeHasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Determine if the subscription is incomplete.
     *
     * @return bool
     */
    public function stripeIncomplete()
    {
        return $this->status === self::STRIPE_STATUS_INCOMPLETE;
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function stripePastDue()
    {
        return $this->status === self::STRIPE_STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function stripeActive()
    {
        return !$this->stripeEnded() &&
            $this->status !== self::STRIPE_STATUS_INCOMPLETE &&
            $this->status !== self::STRIPE_STATUS_INCOMPLETE_EXPIRED &&
            $this->status !== self::STRIPE_STATUS_PAST_DUE &&
            $this->status !== self::STRIPE_STATUS_UNPAID;
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function stripeEnded()
    {
        return $this->stripeCanceled() && !$this->stripeOnGracePeriod();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function stripeCanceled()
    {
        return $this->status == self::STRIPE_STATUS_CANCELED;
    }

    /**
     * Determine if the subscription will be cancelated 
     * 
     * on a custom_date or on trial_period_ends
     *
     * @return boolean
     */
    public function stripeWillBeCancelated()
    {
        return $this->will_be_canceled;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function stripeValid()
    {
        return $this->stripeActive() || $this->stripeOnTrial() || $this->stripeOnGracePeriod();
    }    

    /**
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function stripeHasIncompletePayment()
    {
        return $this->stripePastDue() || $this->stripeIncomplete();
    }        
    

    /**
     * Determine if the subscription is within its grace period
     * 
     * The dead line of the subscription is future
     *
     * @return bool
     */
    public function stripeOnGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }
    

    /*
     * Overriding the database
     */

     
    /**
     * Cancel the subscription at the end of the billing period.
     *
     * @return $this
     */
    public function stripeCancel()
    {
        $stripeSubscription = $this->stripeUpdateSubscription([
            'cancel_at_period_end' => true,
        ]);        

        $this->freshStripeSubscription($stripeSubscription);

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $endsAt = $this->trial_ends_at;
        } else {
            $endsAt = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
        }

        $this->fill([
            'will_be_canceled'     => true,
            'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'ends_at'              => $endsAt,
            'status'               => $stripeSubscription->status,
        ])->save();

        return $this;
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * @param  \DateTimeInterface|int  $endsAt
     * @return $this
     */
    public function stripeCancelAt($endsAt)
    {
        if ($endsAt instanceof DateTimeInterface) {
            $endsAt = $endsAt->getTimestamp();
        }

        $stripeSubscription = $this->stripeUpdateSubscription([
            'cancel_at'          => $endsAt,
            'proration_behavior' => $this->prorateBehavior(),
        ]);

        $this->freshStripeSubscription($stripeSubscription);

        $this->fill([            
            'will_be_canceled'     => true,
            'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'status'               => $stripeSubscription->status,    
            'ends_at'              => $endsAt,
        ])->save();

        return $this;
    }

    /**
     * Cancel the subscription immediately without invoicing.
     *
     * @return $this
     */
    public function stripeCancelNow()
    {        
        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->cancel($this->subscription_id, [
            'prorate' => $this->prorateBehavior() === 'create_prorations',
        ]);

        $this->freshStripeSubscription($stripeSubscription);

        $this->markAsCanceledWithStripeStatus();

        return $this;
    }

    /**
     * Cancel the subscription immediately and invoice.
     *
     * @return $this
     */
    public function stripeCancelNowAndInvoice()
    {
        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->cancel($this->subscription_id, [
            'invoice_now' => true,
            'prorate' => $this->prorateBehavior() === 'create_prorations',
        ]);

        $this->freshStripeSubscription($stripeSubscription);
        
        $this->markAsCanceledWithStripeStatus();
        
        return $this;
    }

    /**
     * Resume the canceled subscription.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function stripeResume()
    {
        if (! $this->onGracePeriod()) {
            throw new LogicException('Unable to resume subscription that is not within grace period.');
        }

        $stripeSubscription = $this->stripeUpdateSubscription([
            'cancel_at_period_end' => false,
            'trial_end' => $this->stripeOnTrial() ? $this->trial_ends_at->getTimestamp() : 'now',
        ]);

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "canceled". Then we shall save this record in the database.
        $this->fill([
            'will_be_cancelated'   => false,
            'status'               => $stripeSubscription->status,
            'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'ends_at'              => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
        ])->save();

        return $this;
    }

    /**
     * Mark the subscription as canceled.
     *
     * @return void
     *
     * @internal
     */
    public function markAsCanceledWithStripeStatus()
    {
        $this->fill([
            'will_be_canceled' => false,
            'status'           => self::STRIPE_STATUS_CANCELED,
            'ends_at'          => Carbon::now(),
        ])->save();
    }

    /**
     * Update the underlying Stripe subscription information for the model.
     *
     * @param  array  $options
     * @return \Stripe\Subscription
     */
    public function stripeUpdateSubscription(array $options = [])
    {
        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->update(
            $this->subscription_id, $options
        );

        return $this->freshStripeSubscription($stripeSubscription);
    }
    

    /**
     * Get the subscription as a Stripe subscription object.
     *
     * @param  array  $expand
     * @return \Stripe\Subscription
     */
    public function asStripeSubscription(array $expand = [])
    {
        if ($this->recentlyStripeSubscrtionFetched instanceOf StripeSubscription) {
            return $this->recentlyStripeSubscrtionFetched;
        }

        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->retrieve(
            $this->subscription_id, ['expand' => $expand]
        );

        return $this->freshStripeSubscription($stripeSubscription);
    }

    /**
     * Sync the Stripe status of the subscription.
     *
     * @return void
     */
    public function stripeSyncStatus()
    {
        $subscription = $this->asStripeSubscription();

        $this->status = $subscription->status;

        $this->save();
    }

    /**
     * Fresh the local memory cache
     *
     * @param StripeSubscription $stripeSubscription
     * @return StripeSubscription
     */
    protected function freshStripeSubscription(StripeSubscription $stripeSubscription)
    {
        return $this->recentlyStripeSubscrtionFetched = $stripeSubscription;
    }
}
