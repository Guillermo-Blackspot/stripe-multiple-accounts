<?php

namespace BlackSpot\StripeMultipleAccounts\Concerns;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Services\StripeService;

trait HasStripeIntegration
{
  	protected $stripeServiceInstance;
  
  	public function getStripeAttribute()
	{
		if ($this->stripeServiceInstance !== null) {
			return $this->stripeServiceInstance;
		}

		return $this->stripeServiceInstance = new StripeService($this);
	}

	public function scopeWithStripeServiceIntegration($query)
	{
		return $query->with([
			'service_integrations' => function($query) {
				$query->where('name', ServiceIntegration::STRIPE_SERVICE)
					->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
			}
		]);
	}

	public function scopeWhereHasStripeServiceIntegration($query)
	{
		return $query->whereHas('service_integrations', function($query) {
		$query->where('name', ServiceIntegration::STRIPE_SERVICE)
				->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME);
		});
	}

	public function scopeWhereHasActiveStripeServiceIntegration($query)
	{
		return $query->whereHas('service_integrations', function($query) {
		$query->where('name', ServiceIntegration::STRIPE_SERVICE)
				->where('short_name', ServiceIntegration::STRIPE_SERVICE_SHORT_NAME)
				->where('active', true);
		});
	}
}
