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
        Schema::create('qurban_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onUpdate('cascade')->onDelete('cascade');
            $table->json('photo_product_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->bigInteger('price');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qurban_products');
    }
};
