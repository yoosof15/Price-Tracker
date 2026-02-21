<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_price_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade'); // <--- nullable()
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('cascade'); // <--- nullable()
            $table->date('date');
            $table->timestamps();

            // <--- Unique Constraint های جدید:
            // برای محصول: محصول در یک تاریخ فقط یک بار فعال باشد
            $table->unique(['product_id', 'date'], 'product_date_unique')->whereNotNull('product_id');
            // برای مکان: مکان در یک تاریخ فقط یک بار فعال باشد
            $table->unique(['location_id', 'date'], 'location_date_unique')->whereNotNull('location_id');

            // <--- اطمینان از اینکه product_id و location_id همزمان null نباشند
            // این را در اعتبارسنجی کنترلر مدیریت میکنیم، نه در دیتابیس.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_price_settings');
    }
};
