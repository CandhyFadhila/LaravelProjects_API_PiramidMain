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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('file_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->text('file_url');
            $table->string('file_mime_type')->nullable();
            $table->string('file_size')->nullable();
            $table->string('reason')->nullable(); // null ketika tidak ditolak
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
