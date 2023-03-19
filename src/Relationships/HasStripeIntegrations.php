<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;

trait HasStripeIntegrations
{
 
  /**
   * Scope by name and short name of the stripe provider
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeStripeService($query)
  {
    return $query->where('name', ServiceIntegration::STRIPE_SERVICE)
              ->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
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
   * For a better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @param bool $evaluatesActiveStatus
   * @return bool
   */
  public function hasStripeServiceIntegration($evaluatesActiveStatus = false)
  {  
    return $this->findStripeServiceIntegration($evaluatesActiveStatus)->isNotEmpty();
  }  

  /**
   * Determine if the owner model has the stripe service integration active
   * 
   * For a better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @return bool
   */
  public function hasActiveStripeServiceIntegration()
  {  
    return $this->findStripeServiceIntegration(true)->isNotEmpty();
  }

  /**
   * Get the stripe service integration if active
   * 
   * For a better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @return object|null
   */
  public function getActiveStripeServiceIntegration()
  {
    return $this->findStripeServiceIntegration(true)->first();
  }

  /**
   * Get the stripe service integration
   * 
   * For a better performance use the scope ->withStripeServiceIntegration() before use 
   * this function
   * 
   * @return object|null
   */
  public function getStripeServiceIntegration()
  {
    return $this->findStripeServiceIntegration(false)->first();
  }


  /**
   * Scope where has stripe service integration
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWhereHasStripeServiceIntegration($query)
  {
    return $query->whereHas('service_integrations', function($query) {
      $query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
            ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME);
    });
  }

  /**
   * Scope where has stripe service integration active
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWhereHasActiveStripeServiceIntegration($query)
  {
    return $query->whereHas('service_integrations', function($query) {
      $query->where('name', ServiceIntegration::SYSTEM_CHARGES_SERVICE)
            ->where('short_name', ServiceIntegration::SYSTEM_CHARGES_SERVICE_SHORT_NAME)
            ->where('active', true);
    });
  }

  /**
   * Scope to load stripe service integration
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithStripeServiceIntegration($query)
  {
    return $query->with(['service_integrations' => function($query){
      $query->select('id','payload','owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::STRIPE_SERVICE)
          ->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
    }]);
  }

  /**
   * Scope to load stripe service integration if it active
   *
   * @param \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeWithActiveStripeServiceIntegration($query)
  {
    return $query->with(['service_integrations' => function($query){
      $query->select('id','payload','owner_id','owner_type','active','name','short_name')
          ->where('name', ServiceIntegration::STRIPE_SERVICE)
          ->where('short_name',ServiceIntegration::STRIPE_SERVICE_SHORT_NAME)
          ->where('active', true);
    }]);
  }
}
