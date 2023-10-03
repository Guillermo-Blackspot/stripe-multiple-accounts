<?php 

namespace BlackSpot\StripeMultipleAccounts;

use BlackSpot\StripeMultipleAccounts\Concerns\HandlesPaymentFailures;
use BlackSpot\StripeMultipleAccounts\Concerns\InteractsWithPaymentBehavior;
use BlackSpot\StripeMultipleAccounts\Concerns\Prorates;
use BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationProduct;
use BlackSpot\StripeMultipleAccounts\SubscriptionUtils;
use BlackSpot\SystemCharges\Exceptions\InvalidStripeSubscription;
use Carbon\Carbon;
use Closure;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Stripe\Subscription as StripeSubscription;

class SubscriptionBuilder
{
    use InteractsWithPaymentBehavior;
    use Prorates;
    use HandlesPaymentFailures;

    // collection_method
    // days_until_due

    /**
     * The model that is subscribing.
     *
     * @var \BlackSpot\StripeMultipleAccounts\Billable|\Illuminate\Database\Eloquent\Model
     */
    protected $owner;
    
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
     * The service integration id
     *
     * @var int
     */
    protected $serviceIntegrationId;

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
    protected $description = null;

    /**
     * The curency to apply to the subscription
     *
     * @var string
     */
    protected $currency = 'mxn';

    /**
     * The date on which the billing cycle should be anchored.
     *
     * @var \DateTimeInterface|null
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
     * The date when the subscription will be canceled
     * 
     * @var \Carbon\Carbon|\Carbon\CarbonInterface|null
     */
    protected $cancelAt = null;

    /**
     * Callback to be called before to send the subscriptio to stripe
     *
     * @var \Closure
     */
    protected $callbackToMapItemsBefore = null;

    /**
     * Define the days or date when the product still active
     *
     * @var int|\DateTimeInterface
     */
    protected $keepProductsActiveUntil = null;

    /**
     * Recurring interval count to combine with the recurring interval
     * 
     * every $one month , every $two days, etc..
     *
     * @var int
     */
    protected $recurringIntervalCount = 1;

    /**
     * Recurring interval
     * 
     * day, week, month or year
     * 
     * @var string
     */
    protected $recurringInterval = null;

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
        $this->serviceIntegrationId   = $this->owner->getSystemChargesServiceIntegration($serviceIntegrationId)->id;
        $this->billingCycleAnchor     = Date::now();

        $existingRelatedSubscription = $this->owner->findStripeSubscriptionByIdentifier($this->serviceIntegrationId, $this->subscriptionIdentifier);

        if ($existingRelatedSubscription) {
            return $existingRelatedSubscription; // exists
        }

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
                    ->filter(fn ($item) => $item->allow_recurring == true)
                    ->filter(fn ($item) => $item->default_price_id != null)
                    ->filter(fn ($item) => $item->service_integration_id == $this->serviceIntegrationId)
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
     * 
     * @throws InvalidStripeSubscription
     */
    public function anchorBillingCycleOn($date)
    {
        if (! $date) {
            $date = Date::now();
        }

        if (! ($date instanceof DateTimeInterface)) {
            $date = Date::parse($date);
        }

        if ($date->isPast() && !$date->isToday()) {
            throw InvalidStripeSubscription::pastDate($this, $date);
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
     * Set the date when the subscription should cancel
     *
     * can be used for define the billing cycles
     * 
     * @param  \DateTimeInterface|int  $date
     * @return $this
     */
    public function cancelAt($date)
    {       
        if ($date != null && ! ($date instanceof \DateTimeInterface)) {
            $date = Date::parse($date);
        }

        $this->cancelAt = $date;

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
        $this->trialExpires = (int) $trialDays;

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
        if ($trialUntil != null && ! ($trialUntil instanceof \DateTimeInterface)) {
            $trialUntil = Date::parse($trialUntil);
        }

        $this->trialExpires = $trialUntil;

        return $this;
    }

    /**
     * Define the days when the products still active after the subscription is cancelled of ended
     * 
     * Days or a concrete date, when null is received the products still active forever
     * 
     * @param null|int|\DateTimeInterface $daysOrDate
     * 
     * @return $this
     */
    public function keepProductsActiveUntil($daysOrDate)
    {
        if (is_numeric($daysOrDate)) {
            $daysOrDate = (int) $daysOrDate;
        }else if ($daysOrDate !== null) {
            $daysOrDate = Date::parse($daysOrDate);
        }

        $this->keepProductsActiveUntil = $daysOrDate;

        return $this;
    }

    /**
     * Interval
     *
     * @param int $interval
     * @return $this
     * 
     * @throws InvalidStripeSubscription
     */
    public function interval($interval)
    {
        if (!in_array($interval, ['day','week','month','year'])) {
            throw InvalidStripeSubscription::unknownInterval($this, $interval);
        }

        $this->recurringInterval = $interval;

        return $this;
    }

    /**
     * Interval count
     *
     * @param int $intervalCount
     * @return $this
     */
    public function intervalCount($intervalCount)
    {
        $this->recurringIntervalCount = (int) ($intervalCount ?? 1);

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
     * @throws \Blackspot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment
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
     * @return \BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription|null
     *
     * @throws \BlackSpot\StripeMultipleAccounts\Exceptions\IncompleteStripePayment|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeSubscription|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeServiceIntegration|\BlackSpot\StripeMultipleAccounts\Exceptions\InvalidStripeCustomer
     */
    public function create($paymentMethodId = null, array $customerOptions = [], array $subscriptionOptions = [])
    {
        if (empty($this->items)) {
            throw InvalidStripeSubscription::emptyItems($this);
        }

        $stripeCustomer = $this->getStripeCustomer($paymentMethodId, $customerOptions);

        $stripeSubscription = $this->owner->getStripeClient($this->serviceIntegrationId)->subscriptions->create(array_merge(
            ['customer' => $stripeCustomer->id], $this->buildPayload(), $subscriptionOptions
        ));
        
        $subscription = $this->createSubscription($stripeSubscription);

        $this->handlePaymentFailure($this->serviceIntegrationId, $subscription, $paymentMethodId);

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
        // $firstItem     = $stripeSubscription->items->first();
        // $isSinglePrice = $stripeSubscription->items->count() === 1;

        /** @var \BlackSpot\StripeMultipleAccounts\Models\ServiceIntegrationSubscription $subscription */
        $subscription = $this->owner->stripe_subscriptions()->create([
            'identified_by'              => $this->subscriptionIdentifier,
            'name'                       => $this->name,
            'customer_id'                => $stripeSubscription->customer,
            'subscription_id'            => $stripeSubscription->id,
            'status'                     => $stripeSubscription->status,
            'trial_ends_at'              => ! $this->skipTrial ? $this->trialExpires : null,
            'billing_cycle_anchor'       => Date::parse($stripeSubscription->current_period_start),
            'current_period_start'       => Date::parse($stripeSubscription->current_period_start),
            'current_period_ends_at'     => Date::parse($stripeSubscription->current_period_end),            
            'service_integration_id'     => $this->serviceIntegrationId,
            'keep_products_active_until' => $this->getKeepProductsActiveUntilForPayload(),
            //'price_id'        => $isSinglePrice ? $firstItem->price->id : null,
        ]);
        
        /** @var \Stripe\SubscriptionItem $item */
        foreach ($stripeSubscription->items as $item) {
            $subscription->stripe_subscription_items()->updateOrCreate(
                ['item_id' => $item->id], // find by item id
                [
                    'stripe_product_id' => $item->metadata['stripe_product_id'],  // \App\Models\Morphs\StripeProduct
                    'quantity'          => $item->quantity ?? 1,
                    'price_id'          => $item->price->id,
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
            'billing_cycle_anchor' => $this->billingCycleAnchor->getTimestamp(),
            'expand'               => ['latest_invoice.payment_intent'],
            'metadata'             => $this->getMetadataForPayload(),
            'items'                => $this->getItemsForPayload(),
            'trial_end'            => $this->getTrialEndsAtForPayload(),
            'cancel_at'            => $this->getCancelAtForPayload(),
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

        // if ($this->callbackToMapItemsBefore instanceof Closure) {
        //     $items = $items->map($this->callbackToMapItemsBefore);
        // }

        return $items->map(function($item){
                    $payload = [
                        'price' => $item['default_price_id'],
                        'metadata' => [
                            'stripe_product_id'   => $item->id,
                            'stripe_product_type' => get_class($item),                            
                            'model_id'            => $item->model_id,          
                            'model_type'          => $item->model_type,
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
     * Get the cancel date 
     * 
     * calculates from the expected invoices or get defined by customer
     *
     * @return null|\DateTimeInterface
     */
    protected function getCancelAtForPayload()
    {
        if ($this->cancelAt) {
            return $this->cancelAt->getTimestamp();
        }

        return null;
    }

    /**
     * Get the trial ending date for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getTrialEndsAtForPayload()
    {
        if ($this->skipTrial) {
            return 'now'; // has not trial days
        }

        if (is_int($this->trialExpires)) {
            
            return $this->billingCycleAnchor->copy()->addDays($this->trialExpires)->getTimestamp(); // For days (int) Determine from the billing cycle anchor

        }else if ($this->trialExpires instanceof DateTimeInterface) {           
            
            if ($this->trialExpires->gt($this->billingCycleAnchor)){    
                return $this->trialExpires->getTimestamp();
            }
        }

        return null;
    }    

    /**
     * Determine the date until the related products still active
     *
     * @return DateTimeInterface|null
     */
    protected function getKeepProductsActiveUntilForPayload()
    {
        if ($this->keepProductsActiveUntil === null) {
            return null;
        }

        if (! $this->cancelAt) {
            return null;
        }

        if (is_int($this->keepProductsActiveUntil)) {
            return $this->cancelAt->copy()->addDays($this->keepProductsActiveUntil);            
        }
            
        if ($this->keepProductsActiveUntil->gt($this->cancelAt)) {
            return $this->keepProductsActiveUntil;
        }

        return $this->cancelAt; // At the same time that is canceled
    }

    /**
     * Get the metadata for the Stripe payload.
     *
     * @return int|string|null
     */
    protected function getMetadataForPayload()
    {
        return array_merge($this->metadata, [
            'owner_id'   => $this->owner->id,
            'owner_type' => get_class($this->owner),
        ]);
    }

}
