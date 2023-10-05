<?php

namespace BlackSpot\StripeMultipleAccounts\Models;

use BlackSpot\ServiceIntegrationsContainer\ServiceIntegration;
use BlackSpot\ServiceIntegrationsContainer\ServiceProvider as ServiceIntegrationsContainerProvider;
use BlackSpot\StripeMultipleAccounts\Concerns\ManagesAuthCredentials;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer;
use BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration;
use BlackSpot\StripeMultipleAccounts\Relationships\BelongsToServiceIntegration;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Stripe\PaymentMethod;

class StripeCustomer extends Model
{
    /** 
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stripe_customers';
    public const TABLE_NAME = 'stripe_customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    
    /**
     * The stripe customer instance
     * 
     * @var \Stripe\Customer|null
     */
    protected $stripeCustomer = null;

    /**
     * Get the value of property from the memo
     *
     * @param string $property
     * 
     * @return \Stripe\Customer|null|string
     */
    protected function getFromMemo($property)
    {
        if (! is_null($this->{$property})) {
            return $this->{$property};
        }

        return ;
    }

    /**
     * Get the stripe customer
     *
     * Fetch to stripe
     * 
     * @param array $expand
     * @return \Stripe\Customer|null
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function asStripe(array $expand = [])
    {
        $this->assertExistsAsStripe();

        // Check the memo
        if (empty($expand)) {
            $stripeCustomer = $this->getFromMemo('stripeCustomer');
            if (! is_null($stripeCustomer)) {
                return $stripeCustomer;
            }
        }

        return $this->stripeCustomer = $this->getClient()->customers->retrieve($this->customer_id, ['expand' => $expand]);
    }


    /**
     * Update as stripe customer
     * 
     * Connect to stripe
     *
     * @param array $opts
     * 
     * @return \Stripe\Customer
     * 
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function updateAsStripe(array $opts)
    {
        $this->assertExistsAsStripe();

        return $this->stripeCustomer = $this->getClient()->customers->update(
            $this->customer_id, $opts
        );
    }

  
    /**
     * PaymentMethods
     */

    public function createSetupIntent(array $opts = [])
    {
        $this->assertExistsAsStripe();

        // Default payment_method_types = ['card']
        $opts['customer'] = $this->customer_id;
        
        return $this->getClient()->setupIntents->create($opts);    
    }
    
    public function getPaymentMethods($type = 'card')
    {
        $this->assertExistsAsStripe();

        return $this->getClient()->customers->allPaymentMethods($this->customer_id, ['type' => $type]);
    }

    public function addPaymentMethod($paymentMethodId)
    {
        $this->assertExistsAsStripe();

        return $this->getClient()->paymentMethods->attach($paymentMethodId, ['customer' => $this->customer_id]);
    }

    public function deletePaymentMethod($paymentMethodId)
    {
        $this->assertExistsAsStripe();

        $this->getClient()->paymentMethods->detach($paymentMethodId);
    }

    public function getOrAddPaymentMethod($paymentMethodId, $type = 'card')
    {
        $stripePaymentMethods = collect(
            $this->getPaymentMethods($type)->data
        );

        $found = $stripePaymentMethods->firstWhere('id', $paymentMethodId);

        if ($found instanceof PaymentMethod) {
            return $found;
        }

        return $this->addPaymentMethod($paymentMethodId);
    }

    public function setDefaultPaymentMethod($paymentMethodId)
    {
        $paymentMethod = $this->getOrAddPaymentMethod($paymentMethodId);

        $this->updateAsStripe([
            'invoice_settings' => ['default_payment_method' => $paymentMethod->id],
        ]);

        return $paymentMethod;
    }

    public function getDefaultPaymentMethod()
    {                
        $stripeCustomer = $this->asStripe();

        if ($stripeCustomer->invoice_settings->default_payment_method) {
            return $stripeCustomer->invoice_settings->default_payment_method;
        }

        // If we can't find a payment method, try to return a legacy source...
        return $stripeCustomer->default_source;
    }
    
    /**
     * Performs Charges
     */
    public function makeCharge($amount, $paymentMethodId, array $opts = [])
    { 
        $opts = array_merge([
            'confirmation_method' => 'automatic',
            'confirm'             => true,
        ], $opts);
        
        $opts['payment_method'] = $paymentMethodId;

        return $this->createPaymentIntent($amount, $opts);
    }

    public function pay($amount, array $opts = [])
    {
        $opts['automatic_payment_methods'] = ['enabled' => true];

        unset($opts['payment_method_types']);

        return $this->createPaymentIntent($amount, $opts);
    }

    public function createPaymentIntentWith($amount, array $paymentMethods, array $opts = [])
    {                
        $opts['payment_method_types'] = $paymentMethods;        

        unset($opts['automatic_payment_methods']);

        return $this->createPaymentIntent($amount, $opts);
    }

    public function createPaymentIntent($amount, array $opts = [])
    {
        $this->assertExistsAsStripe();        

        $opts             = array_merge(['currency' => 'mxn'], $opts);
        $opts['amount']   = $amount;
        $opts['customer'] = $this->customer_id;
        $opts['expand']   = array_merge(($opts['expand'] ?? []), ['latest_charge']);

        return $this->getClient()->paymentIntents->create($opts);
    }

    public function refundPaymentIntent($paymentIntentId, array $opts = [])
    {
        $this->assertExistsAsStripe();  

        return $this->getClient()->refunds->create(array_merge(
            ['payment_intent' => $paymentIntentId], $opts
        ));
    }    

    public function findPaymentIntent($paymentIntentId)
    {
        $this->assertExistsAsStripe();  

        return $this->getClient()->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * It is used for set the stripe customer that belongsTo the local model
     *
     * By default is used in the "createStripeCustomer" method of the "ManagesCustomer" trait
     * 
     * @param \Stripe\Customer $stripeCustomer
     * 
     * @return $this
     */
    public function putStripeCustomer(\Stripe\Customer $stripeCustomer)
    {
        $this->stripeCustomer = $stripeCustomer;

        return $this;
    }

    /**
     * Undocumented function
     *
     * @throws InvalidStripeServiceIntegration|InvalidStripeCustomer
     */
    public function assertExistsAsStripe()
    {
        if (is_null($this->service_integration_id)) {
            throw InvalidStripeServiceIntegration::notYetCreated($this);
        }

        if (is_null($this->customer_id)) {
            throw InvalidStripeCustomer::notYetCreated($this);
        }

        $this->getClient();
    }

    public function getClient()
    {
        return $this->getService()->stripe->getClient();
    }

    public function getService()
    {
        if (! $this->relationLoaded('service_integration')) {
            $this->load('service_integration');
        }

        return $this->service_integration;
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

    /**
     * Get the owner of the stripe customer
     *
     * @return void
     */
    public function owner()
    {
        return $this->morphTo('owner');
    }    

    /**
     * Get the related service integration of this customer
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service_integration()
    {
        return $this->belongsTo(ServiceIntegrationsContainerProvider::getFromConfig('model', ServiceIntegration::class), 'service_integration_id');
    }
}
