<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;

class StripeProduct extends Model
{
    use ManagesAuthCredentials;
    
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stripe_products';
    public const TABLE_NAME = 'stripe_products';

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
        return $this->hasMany(config('stripe-multiple-accounts.relationship_models.subscription_items'), 's_product_id');
    }
}
