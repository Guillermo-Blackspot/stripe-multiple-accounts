<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use App\Models\Morphs\ServiceIntegration;
use Illuminate\Database\Eloquent\Model;

class ServiceIntegrationUser extends Model
{
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_integration_users';
    public const TABLE_NAME = 'service_integration_users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo('owner');   
    }

    public function service_integration()
    {
        return $this->belongsTo(config('stripe-multiple-accounts.relationship_models.stripe_accounts'), 'service_integration_id');
    }
    
}
