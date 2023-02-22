<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeSubscriptionItem extends Model
{
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stripe_subscription_items';
    public const TABLE_NAME = 'stripe_subscription_items';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        //'metadata' => 'array'
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

    public function stripe_subscription()
    {
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.subscriptions'), 's_subscription_id');
    }

    public function stripe_product()
    {
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.products'), 's_product_id');
    }
}
