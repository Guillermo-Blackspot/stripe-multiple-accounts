<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ServiceIntegrationSubscription extends Model
{
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
    protected $with = ['service_integration_items'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'ends_at'       => 'datetime',
        'quantity'      => 'integer',
        'trial_ends_at' => 'datetime',
        'metadata'      => 'array'
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
            case 'canceled':           return 'Cancelado o se agotaron los intentos de pago'; break;
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
        return is_null($this->service_integration_subscription_items->count());
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
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function hasIncompletePayment()
    {
        return $this->pastDue() || $this->incomplete();
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
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
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
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return !is_null($this->ends_at);
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

        if (is_null($stripeSubscription)) {
            throw new \Exception('The subscription not exists.');
        }

        $this->status = $stripeSubscription->status;

        // If the user was on trial, we will set the grace period to end when the trial
        // would have ended. Otherwise, we'll retrieve the end of the billing period
        // period and make that the end of the grace period for this current user.
        if ($this->onTrial()) {
            $this->ends_at = $this->trial_ends_at;
        } else {
            $this->ends_at = Carbon::createFromTimestamp(
                $stripeSubscription->current_period_end
            );
        }

        $this->save();

        return $this;
    }

    /**
     * Update the underlying Stripe subscription information for the model.
     *
     * @param  array  $options
     * @return \Stripe\Subscription
     */
    public function updateStripeSubscription(array $options = [])
    {
        $stripeClientConnection = $this->model->getStripeClientConnection($this->service_integration_id);

        if (is_null($stripeClientConnection)) {
            return ;
        }
        
        return $stripeClientConnection->subscriptions->update(
            $this->subscription_id, $options
        );
    }
    

    /**
     * Accessors
     */
    public function getStatusDescriptionAttribute()
    {
        return static::resolveStripeStatusDescription($this->status);
    }



    public function model()
    {
        return $this->morphTo('model');   
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
