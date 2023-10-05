<?php

namespace BlackSpot\StripeMultipleAccounts\Services;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Services\PaymentIntentsService;
use BlackSpot\StripeMultipleAccounts\Services\Traits\ManageCredentials;
use BlackSpot\StripeMultipleAccounts\Services\Traits\ManagePaymentMethods;
use BlackSpot\StripeMultipleAccounts\Services\Traits\ManagesCustomer;
use BlackSpot\StripeMultipleAccounts\Services\Traits\PerformCharges;
use Illuminate\Database\Eloquent\Model;

class StripeService
{
    use ManagesCustomer;
    use ManageCredentials;
    use ManagePaymentMethods;
    use PerformCharges;

    public $model;

    public function __construct(Model $model, ?Model $billable = null)
    {
        $this->model    = $model;
        $this->billable = $billable;
    }

    public function getService()
    {
        if (get_class($this->model) == ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class) ) {            
            if ($this->model->name == ServiceIntegration::STRIPE_SERVICE && $this->model->short_name == ServiceIntegration::STRIPE_SERVICE_SHORT_NAME) {
                return $this->model;
            }

            throw InvalidStripeServiceIntegration::incorrectProvider($this->model);
        }

        return $this->model->service_integrations
                    ->filter(function($service){
                        return $service->name == ServiceIntegration::STRIPE_SERVICE && 
                        $service->short_name == ServiceIntegration::STRIPE_SERVICE_SHORT_NAME;
                    })
                    ->first();                    
    }

    public function hasService()
    {
        return $this->getService() !== null;
    }  

    public function serviceIsActive()
    {
        return optional($this->getService())->active() == true;
    }

    public function getServiceIfActive()
    {
        $service = $this->getService();
        
        if ($service == null) return ;
        if ($service->active()) return $service;
    }

    public function assertServiceExists()
    {
        if (! $this->hasService()) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);
        }

        $payloadColumn = ServiceIntegrationsContainerProvider::getFromConfig('payload_colum','payload');

        if (! isset($this->getService()->{$payloadColumn})) {
            throw InvalidStripeServiceIntegration::payloadColumnNotFound($this, $payloadColumn);
        }
    }
    
    public function assertServiceIsActive()
    {
        $this->assertServiceExists();

        if (! $this->serviceIsActive()) {
            throw InvalidStripeServiceIntegration::isDisabled($this);
        }
    }
}