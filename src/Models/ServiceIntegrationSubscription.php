<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceIntegrationSubscription extends Model
{
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_integration_subscriptions';
    public const TABLE_NAME = 'service_integration_subscriptions';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array'
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
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.subscription_items'), 'subscription_id');
    }    
}
