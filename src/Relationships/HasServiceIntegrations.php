<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;

trait HasServiceIntegrations
{
  /**
   * Boot on delete method
   */
  public static function bootHasServiceIntegrations()
  {
    static::deleting(function ($model) {
      if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
        return;
      }
      $model->service_integrations()->delete();
    });
  }

  /**
  * Get the service_integrations
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function service_integrations()
  {
    return $this->morphMany(config('stripe-multiple-accounts.relationship_models.stripe_accounts'), 'owner');
  }


  /**
   * Find the stripe service integrations
   *
   * @param boolean $evaluatesActiveStatus
   * @return void
   */
  protected function findStripeServiceIntegration($evaluatesActiveStatus = false)
  {
    return $this->service_integrations
              ->filter(function($service){
                return $service->name == ServiceIntegration::STRIPE_SERVICE && $service->short_name == ServiceIntegration::STRIPE_SERVICE_SHORT_NAME;
              }) 
              ->filter(function($service) use($evaluatesActiveStatus){
                if ($evaluatesActiveStatus) return $service->active == true;
                return true; // continue
              });
  }

  /**
   * Determine if the owner model has the stripe service integration
   *
   * Better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @param boolean $evaluatesActiveStatus
   * @return boolean
   */
  public function hasStripeServiceIntegration($evaluatesActiveStatus = false)
  {  
    return $this->findStripeServiceIntegration($evaluatesActiveStatus)->isNotEmpty();
  }

  /**
   * Determine if the owner model has the stripe service integration active
   * 
   * Better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @return boolean
   */
  public function hasStripeServiceIntegrationActive()
  {  
    return $this->findStripeServiceIntegration(true)->isNotEmpty();
  }

  public function scopeWithStripeServiceIntegration($query)
  {
    return $query->joinStripeServiceIntegration();
  }

  public function scopeWithStripeServiceIntegrationIfActive($query)
  {
    return $query->scopeJoinStripeServiceIntegrationIfActive();
  }

  public function scopeJoinStripeServiceIntegration($query)
  {
    return $query->with(['service_integrations' => function($query){
      $query->select('id','payload','owner_id','owner_type','active','name')
          ->where('name', ServiceIntegration::STRIPE_SERVICE)
          ->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
    }]);
  }

  public function scopeJoinStripeServiceIntegrationIfActive($query)
  {
    return $query->with(['service_integrations' => function($query){
      $query->select('id','payload','owner_id','owner_type','active','name')
          ->where('name', ServiceIntegration::STRIPE_SERVICE)
          ->where('short_name',ServiceIntegration::STRIPE_SERVICE_SHORT_NAME)
          ->where('active', true);
    }]);
  }
}
