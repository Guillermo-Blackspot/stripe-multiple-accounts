<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use BlackSpot\StripeMultipleAccounts\Concerns\InteractsWithPaymentBehavior;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Concerns\Prorates;
use BlackSpot\StripeMultipleAccounts\Models\ProviderMethods\StripeMethods;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class StripeSubscription extends Model
{
    use ManagesAuthCredentials;
    use InteractsWithPaymentBehavior;
    use Prorates;
    use StripeMethods;

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
    protected $with = ['service_integration:id,name,short_name,active,owner_id,owner_type','service_integration_subscription_items'];

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
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Accessors
     */
    public function getStatusDescriptionAttribute()
    {
        return static::resolveStripeStatusDescription($this->status);
    }

    protected function getProviderFunctionAccesor()
    {
        if ($this->service_integration->name == ServiceIntegration::STRIPE_SERVICE && $this->service_integration->short_name == ServiceIntegration::STRIPE_SERVICE_SHORT_NAME) {            
            return 'stripe';
        }
        throw new \Exception("Unknown Provider", 1);
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
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }


    /**
     * Determine if the subscription has a specific price.
     *
     * @param  string  $price
     * @return bool
     */
    public function hasPrice($priceId)
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}($priceId);
    }

    /**
     * Determine if the subscription has multiple prices.
     *
     * @return bool
     */
    public function hasMultiplePrices()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription has a single price.
     *
     * @return bool
     */
    public function hasSinglePrice()
    {
        return $this->{$this->getProviderFunctionAccesor()}.ucfirst(__FUNCTION__)();
    }

    /**
     * Determine if the subscription's trial has expired.
     *
     * @return bool
     */
    public function hasExpiredTrial()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription is incomplete.
     *
     * @return bool
     */
    public function incomplete()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription is past due.
     *
     * @return bool
     */
    public function pastDue()
    {
        return $this->{$this->getProviderFunctionAccesor()}.ucfirst(__FUNCTION__)();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription has ended and the grace period has expired.
     *
     * @return bool
     */
    public function ended()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function canceled()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
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
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }    

    /**
     * Determine if the subscription has an incomplete payment.
     *
     * @return bool
     */
    public function hasIncompletePayment()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
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
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
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
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();        
    }

    /**
     * Cancel the subscription at a specific moment in time.
     *
     * @param  \DateTimeInterface|int  $endsAt
     * @return $this
     */
    public function cancelAt($endsAt)
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}($endsAt);
    }

    /**
     * Cancel the subscription immediately without invoicing.
     *
     * @return $this
     */
    public function cancelNow()
    {        
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
    }

    /**
     * Cancel the subscription immediately and invoice.
     *
     * @return $this
     */
    public function cancelNowAndInvoice()
    {
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();
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
        return $this->{$this->getProviderFunctionAccesor().ucfirst(__FUNCTION__)}();        
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
        $provider = ucfirst($this->getProviderFunctionAccesor());

        return $this->{"markAsCanceledWith{$provider}Status"}();
    }

    /**
     * Sync the provider status of the subscription.
     *
     * @return void
     */
    public function syncProviderStatus()
    {
        return $this->{$this->getProviderFunctionAccesor().'SyncStatus'}();
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

    public function stripe_subscription_items()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.subscription_items'), 'stripe_subscription_id');
    }    
}
