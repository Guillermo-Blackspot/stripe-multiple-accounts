<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use BlackSpot\StripeMultipleAccounts\Concerns\InteractsWithPaymentBehavior;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\Prorates;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Stripe\Subscription;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeSubscriptionItem;

class StripeSubscription extends Model
{
    use ManagesAuthCredentials;
    use InteractsWithPaymentBehavior;
    use Prorates;

    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stripe_subscriptions';
    public const TABLE_NAME = 'stripe_subscriptions';

    public const STRIPE_STATUS_INCOMPLETE = 'incomplete';
    public const STRIPE_STATUS_INCOMPLETE_EXPIRED = 'incomplete_expired';
    public const STRIPE_STATUS_PAST_DUE = 'past_due';
    public const STRIPE_STATUS_UNPAID = 'unpaid';
    public const STRIPE_STATUS_CANCELED = 'canceled';    

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
    protected $with = ['service_integration:id,name,short_name,active,owner_id,owner_type','stripe_subscription_items'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_period_ends_at'              => 'datetime',
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
     * Local memory cache
     *
     * @var \Stripe\Subscription
     */
    protected $recentlyStripeSubscrtionFetched = null;

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
     * Accessors
     */
    public function getStatusDescriptionAttribute()
    {
        return static::resolveStripeStatusDescription($this->status);
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * Automatically takes the service_integration provider (Stripe, another)
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
        return $this->stripe_subscription_items->contains(function ($item) use ($priceId) {
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
        return $this->stripe_subscription_items->count() > 1;
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSinglePrice()
    {
        return ! $this->hasMultiplePrices();
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
        return $this->status === \Stripe\Subscription::STATUS_INCOMPLETE;
    }

    public function incompleteExpired()
    {
        return $this->status === \Stripe\Subscription::STATUS_INCOMPLETE_EXPIRED;
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->status === \Stripe\Subscription::STATUS_PAST_DUE;
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return $this->status == \Stripe\Subscription::STATUS_CANCELED;
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return !$this->ended() &&
            $this->status !== \Stripe\Subscription::STATUS_INCOMPLETE &&
            $this->status !== \Stripe\Subscription::STATUS_INCOMPLETE_EXPIRED &&
            $this->status !== \Stripe\Subscription::STATUS_PAST_DUE &&
            $this->status !== \Stripe\Subscription::STATUS_UNPAID;
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
     * Get the latest payment for a Subscription.
     *
     * @return \Stripe\PaymentIntent
     */
    public function latestPaymentIntent()
    {
        $subscription = $this->asStripeSubscription(['latest_invoice.payment_intent']);

        if ($invoice = $subscription->latest_invoice) {
            return $invoice->payment_intent;
        }
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
        return $this->current_period_ends_at && $this->current_period_ends_at->isFuture();
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

        $this->setAsStripeSubscription($stripeSubscription);

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $endsAt = $this->trial_ends_at;
        } else {
            $endsAt = Carbon::createFromTimestamp($stripeSubscription->current_period_end);
        }

        $this->fill([
            'current_period_start'   => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'current_period_ends_at' => $endsAt,
            'status'                 => $stripeSubscription->status,
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

        $this->setAsStripeSubscription($stripeSubscription);

        $this->fill([            
            'current_period_start'   => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'status'                 => $stripeSubscription->status,    
            'current_period_ends_at' => $endsAt,
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

        $this->setAsStripeSubscription($stripeSubscription);

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

        $this->setAsStripeSubscription($stripeSubscription);
        
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
            'trial_end'            => $this->stripeOnTrial() ? $this->trial_ends_at->getTimestamp() : 'now',
        ]);

        // Finally, we will remove the ending timestamp from the user's record in the
        // local database to indicate that the subscription is active again and is
        // no longer "canceled". Then we shall save this record in the database.
        $this->fill([
            'status'                 => $stripeSubscription->status,
            'current_period_start'   => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
            'current_period_ends_at' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
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
            'status'                 => \Stripe\Subscription::STATUS_CANCELED,
            'current_period_ends_at' => Carbon::now(),
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

        return $this->setAsStripeSubscription($stripeSubscription);
    }

    /**
     * Get the subscription as a Stripe subscription object.
     *
     * @param  array  $expand
     * @return \Stripe\Subscription
     */
    public function asStripeSubscription(array $expand = [])
    {
        if ($this->recentlyStripeSubscrtionFetched instanceOf \Stripe\Subscription) {
            return $this->recentlyStripeSubscrtionFetched;
        }

        $stripeSubscription = $this->getStripeClientConnection()->subscriptions->retrieve(
            $this->subscription_id, ['expand' => $expand]
        );

        return $this->setAsStripeSubscription($stripeSubscription);
    }

    /**
     * Sync the Stripe status of the subscription.
     *
     * @return void
     */
    public function syncStatus()
    {
        $subscription = $this->asStripeSubscription();

        $this->status = $subscription->status;

        $this->save();
    }


    /**
     * Fresh the local memory cache
     *
     * @param Stripe\Subscription $stripeSubscription
     * @return Stripe\Subscription
     */
    protected function setAsStripeSubscription(Subscription $stripeSubscription)
    {
        return $this->recentlyStripeSubscrtionFetched = $stripeSubscription;
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
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }

    public function stripe_subscription_items()
    {        
        return $this->hasMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.subscription_item', StripeSubscriptionItem::class), 'stripe_subscription_id');
    }    
}
