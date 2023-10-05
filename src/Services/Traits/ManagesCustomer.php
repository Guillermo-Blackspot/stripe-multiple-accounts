<?php 

namespace BlackSpot\StripeMultipleAccounts\Services\Traits;

use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use Illuminate\Database\Eloquent\Model;

trait ManagesCustomer
{
    protected $storedCustomers = [];

    public function customerExists(Model $billable)
    {
        try {
            $this->getCustomer($billable);
            return true;
        } catch (InvalidStripeServiceIntegration $err) {
            return false;
        } catch (InvalidStripeCustomer $err) {
            return false;
        }
    }

    public function getCustomer(Model $billable)
    {
        $this->assertCustomerExists($billable);

        $customer = $this->getLocalCustomer($billable);

        $customer->assertExistsAsStripe();

        return $customer;
    }

    public function getCustomerId(Model $billable)
    {
        return $this->getCustomer($billable)->customer_id;
    }

    public function createCustomerIfNotExists(Model $billable, array $opts = [])
    {
        $customer = $this->getLocalCustomer($billable);

        if ($customer !== null) return $customer;
        
        return $this->createCustomer($billable, $opts);
    }
 
    public function createOrGetCustomer(Model $billable, array $opts = [])
    {
        $customer = $this->getLocalCustomer($billable);

        if ($customer !== null) return $customer;

        return $this->createCustomer($serviceIntegrationId, $opts);
    }
    
    public function createCustomer(Model $billable, array $opts = [])
    {   
        // Existing customer      
        $localCustomer = $this->getLocalCustomer($billable);
        
        if ($localCustomer !== null) return $localCustomer;

        // Create customer

        $service = $this->getService();
    
        if (empty($opts)) {
            if (method_exists($billable, 'getStripeCustomerInformation')) {
                $opts = (array) $billable->getStripeCustomerInformation();
            }
        }

        if (! isset($opts)) $opts['metadata'] = [];
            
        $opts['metadata'] = array_merge($opts['metadata'], [                
            'service_integration_id'   => $service->id,
            'service_integration_type' => get_class($service) // ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class),
        ]);

        // Connect to stripe
        $stripeCustomer = $this->getClient()->customers->create($opts);

        // Connect to local database
        $localCustomer = $billable->stripe_customers()->create([
            'owner_name'             => $billable->full_name ?? $billable->name,
            'owner_email'            => $billable->email,
            'service_integration_id' => $service->id,
            'customer_id'            => $stripeCustomer->id
        ]);

        $localCustomer->setRelation('service_integration', $service);
        $localCustomer->putStripeCustomer($stripeCustomer);

        return $localCustomer;
    }

    public function updateCustomer(Model $billable, array $opts = [])
    {
        return $this->getCustomer($billable)->updateAsStripe($opts);
    }

    protected function getLocalCustomer(Model $billable)
    {
        $service = $this->getService();

        if (isset($this->storedCustomers[$billable->getKey()])) {
            return $this->storedCustomers[$billable->getKey()];
        }

        $customer = $service->stripe_customers()
                        ->with('service_integration')
                        ->where([
                            'owner_type' => get_class($billable),
                            'owner_id'   => $billable->getKey(),
                        ])
                        ->first();

        if (is_null($customer)) return null;

        return $this->storedCustomers[$billable->getKey()] = $customer;
    }

    public function assertCustomerExists(Model $billable)
    {        
        if (is_null($this->getLocalCustomer($billable))) {
            throw InvalidStripeCustomer::notYetCreated($billable);
        }
    }

}
