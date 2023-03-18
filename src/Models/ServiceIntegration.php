<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;

class ServiceIntegration extends Model
{
    use ManagesAuthCredentials;
    
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_integrations';
    public const TABLE_NAME = 'service_integrations';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payload' => 'array',
    ];

    public const STRIPE_SERVICE = 'Stripe'; 
    public const STRIPE_SERVICE_SHORT_NAME = 'str'; 


    /**
     * Overwrite cast json method
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }


    public function getShortenedPayloadAttribute()
    {
        $shortened = [];

        $payloadAttribute = config('stripe-multiple-accounts.stripe_integrations.payload.column', 'payload');

        foreach ($this->{$payloadAttribute} as $property => $value) {
            $shortened[$property] = Str::limit($value, 15);
        }

        return $shortened;
    }
     
    /**
     * Get the owner of the service integration
     */
    public function owner()
    {
        return $this->morphTo('owner');
    }

    public function stripe_customers()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.customers'), 'service_integration_id');
    }
    
    public function service_integration_products()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.products'), 'service_integration_id');
    }

    public function service_integration_subscriptions()
    {
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.subscriptions'), 'service_integration_id');
    }

    /**
     * Scopes
     */

    public function scopeStripeService($query)
    {
        return $query->where('name', self::STRIPE_SERVICE)
                    ->where('short_name', self::STRIPE_SERVICE_SHORT_NAME);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
