<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ban_wealth_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('investor_id')->constrained('users')->cascadeOnDelete();
            $table->string('fund_slug');
            $table->string('fund_name');
            $table->string('fund_manager');
            $table->string('horizon', 20);
            $table->boolean('shariah');
            $table->decimal('amount', 20, 2);
            $table->decimal('nav_at_order', 12, 4);
            $table->decimal('estimated_units', 20, 4);
            $table->boolean('monthly')->default(false);
            $table->string('full_name');
            $table->string('nid_number');
            $table->date('date_of_birth');
            $table->string('mobile');
            $table->string('email');
            $table->text('present_address');
            $table->string('bank_name');
            $table->string('bank_branch');
            $table->string('bank_account_number');
            $table->string('routing_number');
            $table->string('tin')->nullable();
            $table->string('bo_account')->nullable();
            $table->string('source_of_funds');
            $table->string('investment_experience');
            $table->string('loss_response');
            $table->boolean('politically_exposed')->default(false);
            $table->string('payment_method')->default('bank_transfer');
            $table->string('payment_proof_path');
            $table->string('payment_proof_name');
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['investor_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ban_wealth_orders');
    }
};
