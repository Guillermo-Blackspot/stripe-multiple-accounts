<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;

trait HasStripeIntegration
{
  /**
   * Find the stripe service integrations
   *
   * @param boolean $evaluatesActiveStatus
   * @return void
   */
  protected function searchStripeService($evaluatesActiveStatus = false)
  {
    return $this->service_integrations
              ->filter(function($service){
                return $service->name == ServiceIntegration::STRIPE_SERVICE && 
                  $service->short_name == ServiceIntegration::STRIPE_SERVICE_SHORT_NAME;
              })
              ->first();
  }

  public function hasStripeService()
  {  
    return $this->searchStripeService() !== null;
  }  

  public function hasActiveStripeService()
  {  
    return optional($this->searchStripeService())->active() == true;
  }

  public function getStripeService()
  {
    return $this->searchStripeService();
  }

  public function getActiveStripeService()
  {
    $service = $this->searchStripeService();
    
    if ($service == null) return ;
    if ($service->active()) return $service;
  }

  public function assertStripeServiceExists()
  {
    if (! $this->hasStripeService()) {
      throw InvalidStripeServiceIntegration::notYetCreated($this);
    }
  }
  
  public function assertActiveStripeServiceExists()
  {
    if (! $this->getActiveStripeService()) {      
      throw InvalidStripeServiceIntegration::isDisabled($this);
    }
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
      $query->where('name', ServiceIntegration::STRIPE_SERVICE)
            ->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
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
      $query->where('name', ServiceIntegration::STRIPE_SERVICE)
            ->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME)
            ->where('active', true);
    });
  }
}
