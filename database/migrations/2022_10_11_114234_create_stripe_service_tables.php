<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeServiceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->id();
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('customer_id');
            
            $table->nullableMorphs('owner'); // \App\Models\User

            $table->foreignId('service_integration_id')
                ->nullable()
                ->constrained('service_integrations')
                ->nullOnDelete();
                
            $table->timestamps();
        });

        Schema::create('stripe_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('current_price', 30)->nullable()->comment('Cents');  
            $table->boolean('allow_recurring')->nullable()->default(false);
            $table->boolean('active')->nullable()->default(true);
            $table->string('unit_label')->nullable();
            $table->json('subscription_settings')->nullable();
            $table->json('metadata')->nullable();
            $table->string('product_id')->unique()->nullable(); //Stripe, Conekta
            $table->string('default_price_id')->nullable();        
            $table->nullableMorphs('model');

            $table->foreignId('service_integration_id')
                ->nullable()
                ->constrained('service_integrations')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('stripe_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('identified_by');
            $table->string('name');
            $table->string('status'); //
            $table->string('customer_id'); // From Stripe or Conekta
            $table->string('subscription_id')->nullable(); // From Stripe or Conekta
            $table->dateTime('trial_ends_at')->nullable();
            $table->integer('expected_invoices')->nullable()->comment('Null is forever'); 
            $table->dateTime('billing_cycle_anchor');
            $table->dateTime('current_period_start')->nullable();    
            $table->dateTime('current_period_ends_at')->nullable();
            $table->dateTime('keep_products_active_until')->nullable()->comment('Null is forever');
            $table->json('metadata')->nullable();
            $table->nullableMorphs('owner'); // \App\Models\User            

            $table->foreignId('service_integration_id')
                ->nullable()
                ->constrained('service_integrations')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('stripe_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id');
            $table->string('quantity')->nullable()->default(1);
            $table->string('price_id')->nullable();
        
            $table->foreignId('stripe_subscription_id')
                ->constrained('stripe_subscriptions')
                ->cascadeOnDelete();

            $table->foreignId('stripe_product_id')
                ->constrained('stripe_products')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stripe_customers', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
        }); 

        Schema::table('stripe_products', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
        }); 
                
        Schema::table('stripe_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);         
        }); 
        
        Schema::table('stripe_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['stripe_product_id']);
            $table->dropForeign(['stripe_subscription_id']);
        });

        Schema::dropIfExists('stripe_customers');
        Schema::dropIfExists('stripe_products');
        Schema::dropIfExists('stripe_subscriptions');
        Schema::dropIfExists('stripe_subscription_items');
    }
}
