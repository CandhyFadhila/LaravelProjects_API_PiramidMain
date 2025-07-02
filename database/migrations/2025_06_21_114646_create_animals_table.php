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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_category_id')->constrained('animal_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('animal_breed_id')->constrained('animal_breeds')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('average_weight');
            $table->timestamp('birth_date');
            $table->integer('stock');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
