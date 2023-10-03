<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegration;

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
    return $this->searchStripeService() !== null
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

}
