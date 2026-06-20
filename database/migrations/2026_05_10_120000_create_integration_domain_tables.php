<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique();
            $table->string('erp_external_id', 128)->nullable()->index();
            $table->string('name');
            $table->unsignedInteger('gross_price_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(19.00);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('warehouse_code', 32)->default('MAIN');
            $table->integer('quantity')->default(0);
            $table->timestamp('synced_from_erp_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_code']);
        });

        Schema::create('integration_orders', function (Blueprint $table) {
            $table->id();
            $table->string('erp_order_number', 64)->unique();
            $table->string('status', 32)->default('new');
            $table->string('currency', 3)->default('EUR');
            $table->string('customer_number', 64)->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 32)->index();
            $table->string('direction', 16)->default('inbound');
            $table->string('status', 16)->index();
            $table->string('reference_key', 128)->nullable()->index();
            $table->string('message', 512)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('failed_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 32)->index();
            $table->string('reference_key', 128)->nullable()->index();
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->string('status', 24)->default('pending_retry')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->unique();
            $table->string('method', 8);
            $table->string('path', 512);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('request_body_preview')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('failed_syncs');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('integration_orders');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('products');
    }
};
