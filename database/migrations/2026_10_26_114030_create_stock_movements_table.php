<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id');
            $table->enum('type', [
                'in',
                'out',
                'transfer',
            ]);
            $table->unsignedBigInteger('quantity');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at');

            $table->index([
                'tenant_id',
                'product_id',
                'created_at',
            ]);
            $table->index([
                'tenant_id',
                'warehouse_id',
                'created_at',
            ]);
            $table->index([
                'tenant_id',
                'product_id',
                'id',
            ]);

            $table->foreign(['tenant_id', 'product_id'])->references(['tenant_id', 'id'])->on('products');
            $table->foreign(['tenant_id', 'warehouse_id'])->references(['tenant_id', 'id'])->on('warehouses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
