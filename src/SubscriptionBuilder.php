<?php 

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\InteractsWithPaymentBehavior;
use BlackSpot\StripeMultipleAccounts\Concerns\Prorates;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Stripe\Subscription as StripeSubscription;

class SubscriptionBuilder
{
    use InteractsWithPaymentBehavior;
    use Prorates;

    /**
     * The model that is subscribing.
     *
     * @var \BlackSpot\StripeMultipleAccounts\Billable|\Illuminate\Database\Eloquent\Model
     */
    protected $owner;
    
    /**
     * The service integration id
     *
     * @var int
     */
    protected $serviceIntegrationId;

    /**
     * The subscription identifier
     * 
     * It needs to be unique by user
     * for prevents that one user can be subscribed many times to same subscriptable item
     *
     * @var string
     */
    protected $subscriptionIdentifier;


    /**
     * The subscription name
     *
     * @var string
     */
    protected $name;

    /**
     * Collection of Service Integration Products 
     * 
     * The products synced to some stripe account
     *
     * @var \Illuminate\Support\Collection <\BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct>
     */
    protected $items;

    /**
     * The metadata to apply to the subscription.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * The description to apply to the subscription
     *
     * @var string
     */
    protected $description = '';

    /**
     * The curency to apply to the subscription
     *
     * @var string
     */
    protected $currency = 'mxn';

    /**
     * The date on which the billing cycle should be anchored.
     *
     * @var int|null
     */
    protected $billingCycleAnchor = null;

    /**
     * The date and time the trial will expire.
     *
     * @var \Carbon\Carbon|\Carbon\CarbonInterface|null
     */
    protected $trialExpires;

    /**
     * Indicates that the trial should end immediately.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * Callback to be called before to send the subscriptio to stripe
     *
     * @var \Closure
     */
    protected $callbackToMapItemsBefore = null;

    /**
     * Constructor
     * 
     * @param \BlackSpot\StripeMultipleAccounts\Billable|\Illuminate\Database\Eloquent\Model  $owner
     * @param string $identifier
     * @param string $name
     * @param Illuminate\Database\Eloquent\Model|Illuminate\Database\Eloquent\Model<\BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct> $items
     * @param int|null $serviceIntegrationId
     */
    public function __construct(EloquentModel $owner, $identifier, $name, $items, $serviceIntegrationId = null)
    {
        $this->owner                  = $owner;
        $this->subscriptionIdentifier = $identifier;
        $this->name                   = $name;
        $this->items                  = $items instanceof EloquentModel ? Collection::make([$items]) : $items;

        if ($this->owner->getStripeServiceIntegration($serviceIntegrationId) == null){
            return ;
        }

        $this->serviceIntegrationId = $serviceIntegrationId ?? $this->owner->getStripeServiceIntegration()->id;
        
        if (! $this->validItems()) {
            return ;
        }        
    }    

    /**
     * The subscriptable items must pass the validations
     *
     * @return boolean
     */
    protected function validItems()
    {
        return $this->items
                    ->filter(function($item){
                        return $item->allow_recurring == true;
                    })
                    ->filter(function($item){
                        return $item->default_price_id != null;
                    })
                    ->filter(function($item){
                        return $item->service_integration_id == $this->serviceIntegrationId;
                    })
                    ->isNotEmpty();
    }


    /**
     * The metadata to apply to a new subscription.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function withMetadata($metadata)
    {
        $this->metadata = (array) $metadata;

        return $this;
    }

    /**
     * Change the billing cycle anchor on a subscription creation.
     *
     * @param  \DateTimeInterface|int  $date
     * @return $this
     */
    public function anchorBillingCycleOn($date)
    {
        if ($date instanceof DateTimeInterface) {
            $date = $date->getTimestamp();
        }

        $this->billingCycleAnchor = $date;

        return $this;
    }

    /**
     * The description to apply to a new subscription.
     *
     * @param  array  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = (string) $description;

        return $this;
    }

    /**
     * The currency to apply to a new subscription.
     *
     * @param  array  $currency
     * @return $this
     */
    public function currency($currency)
    {
        $this->currency = (string) $currency;

        return $this;
    }

    /**
     * Force the trial to end immediately.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Specify the number of days of the trial.
     *
     * @param  int  $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        if ($trialDays != null || $trialDays != 0) {
            $this->trialExpires = Carbon::now()->addDays($trialDays);
        }

        return $this;
    }

    /**
     * Specify the ending date of the trial.
     *
     * @param  \Carbon\Carbon|\Carbon\CarbonInterface  $trialUntil
     * @return $this
     */
    public function trialUntil($trialUntil)
    {
        $this->trialExpires = $trialUntil;

        return $this;
    }

    /**
     * Map the items before to send to stripe
     *
     * @param Closure $callback
     * @return void
     */
    public function mapItems(Closure $callback)
    {
        $this->callbackToMapItemsBefore = $callback;

        return $this;
    }

    /**
     * Add a new Stripe subscription to the Stripe model.
     *
     * Customers who already has a default payment method they may invoke this method "add"
     * 
     * @param  array  $customerOptions
     * @param  array  $subscriptionOptions
     * @return \Blackspot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription|null
     *
     * @throws \Exception
     */
    public function add(array $customerOptions = [], array $subscriptionOptions = [])
    {
        return $this->create(null, $customerOptions, $subscriptionOptions);
    }

    /**
     * Create a new Stripe subscription.
     *
     * @param  string  $paymentMethodId
     * @param  array  $customerOptions
     * @param  array  $subscriptionOptions
     * @return \Blackspot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription|null
     *
     * @throws \Exception
     */
    public function create($paymentMethodId = null, array $customerOptions = [], array $subscriptionOptions = [])
    {
        if (empty($this->items)) {
            throw new Exception('At least one price is required when starting subscriptions.');
        }

        $stripeCustomer = $this->getStripeCustomer($paymentMethodId, $customerOptions);

        $stripeSubscription = $this->owner->getStripeClientConnection($this->serviceIntegrationId)->subscriptions->create(array_merge(
            ['customer' => $stripeCustomer->id], $this->buildPayload(), $subscriptionOptions
        ));
        
        $subscription = $this->createSubscription($stripeSubscription);

        $stripeSubscription->subscriptions->update($stripeSubscription->id, array_merge($stripeSubscription['metadata'], [
            'stripe_subscription_id'   => $subscription->id,
            'stripe_subscription_type' => config('stripe-multiple-accounts.relationship_models.subscriptions'),
        ]));

        //$this->handlePaymentFailure($subscription, $paymentMethod);

        return $subscription;
    }

    /**
     * Create the Eloquent Subscription.
     *
     * @param  \Stripe\Subscription  $stripeSubscription
     * @return \BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription
     */
    protected function createSubscription(StripeSubscription $stripeSubscription)
    {
        $firstItem = $stripeSubscription->items->first();
        $isSinglePrice = $stripeSubscription->items->count() === 1;

        /** @var \BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription $subscription */
        $subscription = $this->owner->service_integration_subscriptions()->create([
            'identified_by'          => $this->subscriptionIdentifier,
            'name'                   => $this->name,
            'customer_id'            => $stripeSubscription->customer,
            'subscription_id'        => $stripeSubscription->id,
            'status'                 => $stripeSubscription->status,
            'trial_ends_at'          => ! $this->skipTrial ? $this->trialExpires : null,
            'currency'               => $stripeSubscription->currency,
            'will_be_canceled'       => false,
            'billing_cycle_anchor'   => $stripeSubscription->current_period_start,
            'current_period_start'   => $stripeSubscription->current_period_start,
            'current_period_ends_at' => $stripeSubscription->current_period_end,            
            'service_integration_id' => $this->serviceIntegrationId,
            //'price_id'        => $isSinglePrice ? $firstItem->price->id : null,
        ]);

        
        /** @var \Stripe\SubscriptionItem $item */
        foreach ($stripeSubscription->items as $item) {
            $subscription->service_integration_subscription_items()->updateOrCreate(
                ['item_id' => $item->id], // find by item id
                [
                    's_product_id'    => $item->metadata['service_integration_product_id'],  // \App\Models\Morphs\ServiceIntegrationProduct
                    'quantity'        => $item->quantity ?? 1,
                    'price_id'        => $item->price->id,
                ]
            );
        }

        return $subscription;
    }

    /**
     * Get the Stripe customer instance for the current user and payment method.
     *
     * @param  string|null  $paymentMethodId
     * @param  array  $options
     * @return \Stripe\Customer
     */
    protected function getStripeCustomer($paymentMethodId = null, array $options = [])
    {
        $customer = $this->owner->createOrGetStripeCustomer($this->serviceIntegrationId, $options);

        if ($paymentMethodId) {
            $this->owner->updateDefaultStripePaymentMethod($this->serviceIntegrationId, $paymentMethodId);
        }

        return $customer;
    }

    /**
     * Build the payload for subscription creation.
     *
     * @return array
     */
    protected function buildPayload()
    {
        $payload = array_filter([
            'description'          => $this->description,            
            'currency'             => $this->currency,
            'billing_cycle_anchor' => $this->billingCycleAnchor,
            'expand'               => ['latest_invoice.payment_intent'],
            'metadata'             => $this->getMetadataForPayload(),
            'items'                => $this->getItemsForPayload(),
            'trial_end'            => $this->getTrialEndForPayload(),
            'off_session'          => true,
            //'automatic_tax'       => $this->automaticTaxPayload(),
            //'coupon'              => $this->couponId,
            'payment_behavior'    => $this->paymentBehavior(),
            //'promotion_code'      => $this->promotionCodeId,
            'proration_behavior'  => $this->prorateBehavior(),
        ]);

        // if ($taxRates = $this->getTaxRatesForPayload()) {
        //     $payload['default_tax_rates'] = $taxRates;
        // }

        return $payload;
    }

    /**
     * Build the items for the subscription items
     *
     * @return array
     */
    protected function getItemsForPayload()
    {
        $items = $this->items;

        if ($this->callbackToMapItemsBefore instanceof Closure) {
            $items = $items->map($this->callbackToMapItemsBefore);
        }

        return $items->map(function($item){
                    $payload = [
                        'price' => $item['default_price_id'],
                        'metadata' => [
                            'stripe_product_id'   => $item->id,
                            'stripe_product_type' => get_class($item),
                            'owner_id'            => $item->owner_id,          
                            'owner_type'          => $item->owner_type,
                        ]
                    ];

                    if (!isset($item['stripe_metadata'])) {
                        return $payload;
                    }

                    $payload['metadata'] = array_merge($payload['metadata'], $item['stripe_metadata']);

                    return $payload;
                })
                ->values()
                ->all();
    }

    /**
     * Get the trial ending date for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getTrialEndForPayload()
    {
        if ($this->skipTrial) {
            return 'now';
        }

        if ($this->trialExpires) {
            return $this->trialExpires->getTimestamp();
        }
    }    

    /**
     * Get the metadata for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getMetadataForPayload()
    {
        return array_merge($this->metadata, [
            'owner_id'                 => $this->owner->id,
            'owner_type'               => get_class($this->owner),
            'service_integration_id'   => $this->serviceIntegrationId,
            'service_integration_type' => config('stripe-multiple-accounts.relationship_models.stripe_accounts'),
        ]);
    }

}
