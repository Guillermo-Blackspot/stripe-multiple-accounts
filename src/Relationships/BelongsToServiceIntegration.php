<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\ServiceIntegrationsContainer\Models\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;

trait BelongsToServiceIntegration
{
    /**
     * Get the related service integration of this customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }
    
    /**
     * Scope by service_integration_id
     * 
     * @param \Illuminate\Database\Query\Builder
     * @param int $serviceIntegrationId
     */
    public function scopeServiceIntegration($query, $serviceIntegrationId)
    {
        return $query->where('service_integration_id', $serviceIntegrationId);
    }   
}