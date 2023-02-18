<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use BlackSpot\StripeMultipleAccounts\Concerns\InteractsWithPaymentBehavior;
use BlackSpot\StripeMultipleAccounts\Concerns\Prorates;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;
use Stripe\Subscription as StripeSubscription;
use LogicException;

class ServiceIntegrationSubscription extends Model
{
    use ManagesAuthCredentials;
    use InteractsWithPaymentBehavior;
    use Prorates;

    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_integration_subscriptions';
    public const TABLE_NAME = 'service_integration_subscriptions';

    public const STRIPE_STATUS_INCOMPLETE = 'incomplete';
    public const STRIPE_STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    public const STRIPE_STATUS_PAST_DUE = 'past_due';
    public const STRIPE_STATUS_UNPAID = 'unpaid';
    public const STRIPE_STATUS_CANCELED = 'canceled';
    
    /**
     * Local memory cache
     *
     * @var \Stripe\Subscription
     */
    protected $recentlyStripeSubscrtionFetched = null;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['service_integration_subscription_items'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'ends_at'              => 'datetime',
        'quantity'             => 'integer',
        'current_period_start' => 'datetime',
        'trial_ends_at'        => 'datetime',
        'metadata'             => 'array'        
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

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
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }


    /**
     * Determine if the subscription has a specific price.
     *
     * @param  string  $price
     * @return bool
     */
    public function hasPrice($priceId)
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
    public function hasMultiplePrices()
    {
        return $this->service_integration_subscription_items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSinglePrice()
    {
        return !$this->hasMultiplePrices();
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Determine if the subscription is incomplete.
     *
     * @return bool
     */
    public function incomplete()
    {
        return $this->status === self::STRIPE_STATUS_INCOMPLETE;
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->status === self::STRIPE_STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return !$this->ended() &&
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
    public function ended()
    {
        return $this->canceled() && !$this->onGracePeriod();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
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
    public function willBeCancelated()
    {
        return $this->will_be_canceled;
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }    

    /**
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function hasIncompletePayment()
    {
        return $this->pastDue() || $this->incomplete();
    }        
    

    /**
     * Determine if the subscription is within its grace period
     * 
     * The dead line of the subscription is future
     *
     * @return bool
     */
    public function onGracePeriod()
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
    public function cancel()
    {
        $stripeSubscription = $this->updateStripeSubscription([
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
    public function cancelAt($endsAt)
    {
        if ($endsAt instanceof DateTimeInterface) {
            $endsAt = $endsAt->getTimestamp();
        }

        $stripeSubscription = $this->updateStripeSubscription([
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
    public function cancelNow()
    {        
        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->cancel($this->subscription_id, [
            'prorate' => $this->prorateBehavior() === 'create_prorations',
        ]);

        $this->freshStripeSubscription($stripeSubscription);

        $this->markAsCanceled();

        return $this;
    }

    /**
     * Cancel the subscription immediately and invoice.
     *
     * @return $this
     */
    public function cancelNowAndInvoice()
    {
        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->cancel($this->subscription_id, [
            'invoice_now' => true,
            'prorate' => $this->prorateBehavior() === 'create_prorations',
        ]);

        $this->freshStripeSubscription($stripeSubscription);
        
        $this->markAsCanceled();
        
        return $this;
    }

    /**
     * Resume the canceled subscription.
     *
     * @return $this
     *
     * @throws \LogicException
     */
    public function resume()
    {
        if (! $this->onGracePeriod()) {
            throw new LogicException('Unable to resume subscription that is not within grace period.');
        }

        $stripeSubscription = $this->updateStripeSubscription([
            'cancel_at_period_end' => false,
            'trial_end' => $this->onTrial() ? $this->trial_ends_at->getTimestamp() : 'now',
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
    public function markAsCanceled()
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
    public function updateStripeSubscription(array $options = [])
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
     * Fresh the local memory cache
     *
     * @param StripeSubscription $stripeSubscription
     * @return StripeSubscription
     */
    protected function freshStripeSubscription(StripeSubscription $stripeSubscription)
    {
        return $this->recentlyStripeSubscrtionFetched = $stripeSubscription;
    }

    /**
     * Accessors
     */
    public function getStatusDescriptionAttribute()
    {
        return static::resolveStripeStatusDescription($this->status);
    }

    /**
     * Relationships
     */

    public function owner()
    {
        return $this->morphTo('owner');   
    }

    public function service_integration()
    {
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.stripe_accounts'), 'service_integration_id');
    }

    public function service_integration_subscription_items()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.subscription_items'), 's_subscription_id');
    }    
}
