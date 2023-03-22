<?php

namespace BlackSpot\StripeMultipleAccounts\Relationships;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeProduct;
use BlackSpot\StripeMultipleAccounts\Models\StripeProduct;
use BlackSpot\StripeMultipleAccounts\ProductBuilder;
use BlackSpot\StripeMultipleAccounts\SubscriptionSettingsAccesorsAndMutators;

trait HasStripeProducts
{
  use SubscriptionSettingsAccesorsAndMutators;

  protected $localStripeProductsFound = [];


  /**
   * Boot on delete method
   */
  public static function bootHasStripeProducts()
  {
    static::deleting(function ($model) {
        if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
            return;
        }
        $model
          ->stripe_products()
          ->update([
            'model_id'   => null,
            'model_type' => null
          ]);
    });
  }

  /**
   * Create a stripe product with this model
   *
   * @param int $serviceIntegrationId
   * @param string $productName
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function newStripeProduct($serviceIntegrationId, $productName)
  {
    return new ProductBuilder($this, $serviceIntegrationId, $productName);
  }

  /**
   * Determine if the model was synced with stripe
   *
   * local connection
   * 
   * @param int $serviceIntegrationId
   * 
   * @return bool
   */
  public function syncedWithStripe($serviceIntegrationId)
  {
    return $this->asLocalStripeProduct($serviceIntegrationId) !== null;
  }

  /**
   * Get the model was synced with stripe in the local database
   *
   * local connection
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   */
  public function findStripeProduct($serviceIntegrationId)
  {
    return $this->asLocalStripeProduct($serviceIntegrationId);
  }

  /**
   * Delete the model synced with stripe
   *
   * Local query and Stripe Api connection
   * 
   * The stripe product will be disabled and the local register will be deleted
   * StripePHP api not allows delete products, you must delete it from the dashboard
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function deleteStripeProduct($serviceIntegrationId)
  {
    $this->assertStripeProductExists($serviceIntegrationId);

    return $this->asLocalStripeProduct($serviceIntegrationId)->deleteStripeProduct();
  }

  /**
   * Active the stripe product
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function activeStripeProduct($serviceIntegrationId)
  {
    return $this->updateStripeProduct($serviceIntegrationId, ['active' => true]);
  }

  /**
   * Disable the stripe product
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function disableStripeProduct($serviceIntegrationId)
  {
    return $this->updateStripeProduct($serviceIntegrationId, ['active' => false]);
  }

  /**
   * Update the model synced with stripe
   *
   * Local query and Stripe Api connection
   * 
   * @param int $serviceIntegrationId
   * @param array $opts
   * @return \BlackSpot\StripeMultipleAccounts\Models\StripeProduct
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function updateStripeProduct($serviceIntegrationId, array $opts = [])
  {
    $this->assertStripeProductExists($serviceIntegrationId);

    return $this->localStripeProductsFound[$serviceIntegrationId] = $this->asLocalStripeProduct($serviceIntegrationId)->updateStripeProduct($opts);
  }

  /**
   * Disable the stripe product
   *
   * Local query
   * 
   * @param int $serviceIntegrationId
   * @return int
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function countStripeProductsIncludedInSubscriptionsAsItems($serviceIntegrationId)
  {
    $this->assertStripeProductExists($serviceIntegrationId);

    return $this->asLocalStripeProduct($serviceIntegrationId)->stripe_subscription_items()->distinct('stripe_subscription_id')->count();
  }

  /**
   * Get the local stripe customer
   *
   * @param int $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\StripeCustomer
   * 
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */    
  public function asLocalStripeProduct($serviceIntegrationId)
  {
    if (isset($this->localStripeProductsFound[$serviceIntegrationId])) {
      return $this->localStripeProductsFound[$serviceIntegrationId];
    }

    $localProduct = $this->stripe_products()->serviceIntegration($serviceIntegrationId)->with('service_integration')->first();

    if (is_null($localProduct)) {
      unset($this->localStripeProductsFound[$serviceIntegrationId]);
    }

    return $this->localStripeProductsFound[$serviceIntegrationId] = $localProduct;
  }

  /**
   * Determine if the customer has a Stripe customer ID and throw an exception if not.
   *
   * @param int|null $serviceIntegrationId
   * @return \BlackSpot\StripeMultipleAccounts\StripeCustomer
   *
   * @throws \InvalidStripeServiceIntegration|InvalidStripeCustomer
   */
  public function assertStripeProductExists($serviceIntegrationId = null)
  {
    $localStripeProduct = $this->asLocalStripeProduct($serviceIntegrationId);

    if (is_null($localStripeProduct)) {
      throw InvalidStripeProduct::notYetCreated($this);
    }

    $localStripeProduct->assertExistsAsStripe();

    $this->localStripeProductsFound[$serviceIntegrationId] = $localStripeProduct;
  }

  /**
  * Get the stripe_products
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
  */
  public function stripe_products()
  {
    return $this->morphMany(ServiceIntegrationsContainerProvider::getFromConfig('stripe_models.product', StripeProduct::class), 'model');
  }
}
