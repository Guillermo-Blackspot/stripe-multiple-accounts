<?php

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Models\StripeCustomer;
use BlackSpot\StripeMultipleAccounts\Services\StripeService;

trait BillableForStripe
{
    protected $stripeServiceInstance;

    /**
     * Boot on delete method
     */
    public static function bootBillableForStripe()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            // Preserve the stripe users
            $model->stripe_customers()->update([
                'owner_id'   => null,
                'owner_type' => null
            ]);
        });
    }

    public function usingStripeService($serviceIntegration)
    {
        if ($this->stripeServiceInstance !== null) {
            return $this->stripeServiceInstance;
        }

        return $this->stripeServiceInstance = new StripeService($serviceIntegration);
    }

    /**
     * Get the stripe_customers
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function stripe_customers()
    {
        return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.customer', StripeCustomer::class), 'owner');
    }
}