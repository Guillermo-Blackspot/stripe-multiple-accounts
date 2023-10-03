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
    use ManagesAuthCredentials; // FindBy the related "service_integration_id"
    use BelongsToServiceIntegration;

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
        
        $stripe = $this->getStripeClient($this->service_integration_id);

        return $this->stripeCustomer = $stripe->customers->retrieve($this->customer_id, ['expand' => $expand]);
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
        
        return $this->stripeCustomer = $this->getStripeClient($this->service_integration_id)->customers->update(
            $this->customer_id, $opts
        );
    }

    /**
     * PaymentSources
     */

    public function addStripePaymentMethodSource($tokenId, array $opts = [])
    {
        $this->assertExistsAsStripe();

        $opts['source'] = $tokenId;

        return $this->getStripeClient($this->service_integration_id)->customers->createSource($this->customer_id, $opts);
    }

    public function deleteStripePaymentMethodSource($sourceId, array $opts = [])
    {
        $this->assertExistsAsStripe();

        return $this->getStripeClient($this->service_integration_id)->customers->deleteSource($this->customer_id, $sourceId, null, $opts);    
    }

    /**
     * PaymentMethods
     */


    public function createStripeSetupIntent(array $opts = [])
    {
        $this->assertExistsAsStripe();

        // Default payment_method_types = ['card']
        $opts['customer'] = $this->customer_id;
        
        return $this->getStripeClient($this->service_integration_id)->setupIntents->create($opts);    
    }
    
    public function getStripePaymentMethods($type = 'card')
    {
        $this->assertExistsAsStripe();

        return $this->getStripeClient($this->service_integration_id)->customers->allPaymentMethods($this->customer_id, ['type' => $type]);
    }

    public function addStripePaymentMethod($paymentMethodId)
    {
        $this->assertExistsAsStripe();

        return $this->getStripeClient($this->service_integration_id)->paymentMethods->attach($paymentMethodId, ['customer' => $this->customer_id]);
    }

    public function deleteStripePaymentMethod($paymentMethodId)
    {
        $this->assertExistsAsStripe();

        $this->getStripeClient($this->service_integration_id)->paymentMethods->detach($paymentMethodId);
    }

    public function getOrAddStripePaymentMethod($paymentMethodId, $type = 'card')
    {
        $stripePaymentMethods = collect(
            $this->getStripePaymentMethods($type)->data
        );

        $found = $stripePaymentMethods->firstWhere('id', $paymentMethodId);

        if ($found instanceof PaymentMethod) {
            return $found;
        }

        return $this->addStripePaymentMethod($paymentMethodId);
    }

    public function setDefaultStripePaymentMethod($paymentMethodId)
    {
        $paymentMethod = $this->getOrAddStripePaymentMethod($paymentMethodId);

        $this->updateAsStripe([
            'invoice_settings' => ['default_payment_method' => $paymentMethod->id],
        ]);

        return $paymentMethod;
    }

    public function getDefaultStripePaymentMethod()
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
    public function makeStripeCharge($amount, $paymentMethodId, array $opts = [])
    { 
        $opts = array_merge([
            'confirmation_method' => 'automatic',
            'confirm'             => true,
        ], $opts);
        
        $opts['payment_method'] = $paymentMethodId;

        return $this->createStripePaymentIntent($amount, $opts);
    }

    public function stripePay($amount, array $opts = [])
    {
        $opts['automatic_payment_methods'] = ['enabled' => true];

        unset($opts['payment_method_types']);

        return $this->createStripePaymentIntent($amount, $opts);
    }

    public function createStripePaymentIntentWith($amount, array $paymentMethods, array $opts = [])
    {                
        $opts['payment_method_types'] = $paymentMethods;        

        unset($opts['automatic_payment_methods']);

        return $this->createStripePaymentIntent($amount, $opts);
    }

    public function createStripePaymentIntent($amount, array $opts = [])
    {
        $this->assertExistsAsStripe();        

        $opts = array_merge(['currency' => 'mxn'], $opts);
        $opts['amount']   = $amount;
        $opts['customer'] = $this->customer_id;

        return $this->getStripeClient($this->service_integration_id)->paymentIntents->create($opts);
    }

    public function refundStripePaymentIntent($paymentIntentId, array $opts = [])
    {
        $this->assertExistsAsStripe();  

        return $this->getStripeClient($this->service_integration_id)->refunds->create(array_merge(
            ['payment_intent' => $paymentIntentId], $opts
        ));
    }    

    public function findStripePaymentIntent($paymentIntentId)
    {
        $this->assertExistsAsStripe();  

        return $this->getStripeClient($this->service_integration_id)->paymentIntents->retrieve($paymentIntentId);
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

        $this->getStripeClient($this->service_integration_id); 
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
}
