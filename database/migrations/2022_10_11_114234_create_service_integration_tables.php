<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceIntegrationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_integrations', function (Blueprint $table) {
            $table->id();            
            $table->string('name');
            $table->char('short_name', 10);
            $table->string('documentation_link')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('active')->nullable()->default(0);
            $table->morphs('owner');
            $table->timestamps();
        });

        Schema::create('stripe_users', function (Blueprint $table) {
            $table->id();        

            $table->morphs('model'); // \App\Models\User

            $table->foreignId('service_integration_id')
                ->constrained('service_integrations')
                ->cascadeOnDelete();

            $table->string('customer_id')->unique();
            $table->timestamps();
        });

        Schema::create('service_integration_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('current_price', 30)->nullable()->comment('Cents');  
            $table->boolean('allow_recurring')->nullable()->default(false);
            $table->boolean('active')->nullable()->default(true);
            $table->json('metadata')->nullable();            
            $table->string('product_id')->unique()->nullable(); //Stripe, Conekta
            $table->string('default_price_id')->nullable();
            $table->morphs('model');
            $table->foreignId('service_integration_id')
                ->constrained('service_integrations')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::create('service_integration_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('id_name'); // composed by: model_class_model_id_subscriptable_item_class_subscriptable_item_id
            $table->string('customer_id');
            $table->string('subscription_id')->unique(); // Stripe, conekta
            $table->string('status'); //
            $table->string('price_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('currency');
            $table->morphs('model'); // \App\Models\User
            $table->json('metadata')->nullable();

            $table->foreignId('service_integration_id')
                ->constrained('service_integrations')
                ->cascadeOnDelete();

            $table->timestamps();
        });

        Schema::create('service_integration_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_id')->unique();
            $table->string('main_product_id');
            $table->string('price_id');
            $table->string('quantity')->nullable()->default(1);

            $table->foreignId('subscription_id')
                ->constrained('service_integration_subscriptions')
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
        Schema::dropIfExists('service_integrations');

        Schema::table('stripe_users', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
            $table->dropColumn('service_integration_id');            
        }); 

        Schema::dropIfExists('stripe_users');

        Schema::table('service_integration_products', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
            $table->dropColumn('service_integration_id');            
        }); 

        Schema::dropIfExists('service_integration_products');

        Schema::table('service_integration_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
            $table->dropColumn('service_integration_id');            
        }); 

        Schema::dropIfExists('service_integration_subscriptions');

        Schema::table('service_integration_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropColumn('subscription_id');            
        }); 

        Schema::dropIfExists('service_integration_subscription_items');
    }
}
