<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable()->unique();
            $table->string('merchant_txnid', 64)->unique();
            $table->string('plan_slug', 64);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->json('customer_snapshot');
            $table->json('plan_snapshot');
            $table->json('business_snapshot');
            $table->json('policy_snapshot');
            $table->string('policy_version', 64);
            $table->text('acknowledgement');
            $table->timestamp('accepted_at');
            $table->timestamp('client_accepted_at')->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->string('delivery_confirmed_ip', 45)->nullable();
            $table->text('delivery_confirmed_user_agent')->nullable();
            $table->text('delivery_acknowledgement')->nullable();
            $table->timestamps();
        });

        Schema::create('membership_order_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_order_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('kind', 40);
            $table->string('deduplication_key')->nullable()->unique();
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->string('recipient')->nullable();
            $table->string('status', 30)->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Evidence is intentionally retained. Reversing this deployment must not delete records.
    }
};
