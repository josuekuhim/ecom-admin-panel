<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlite_images';

    public function up(): void
    {
        // Mirror minimal columns needed for relations (no FKs across DBs)
        Schema::connection($this->connection)->create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index(); // reference id only
            $table->binary('image_data')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_filename')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('image_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->connection)->create('drop_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drop_id')->index(); // reference id only
            $table->binary('image_data')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('original_filename')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('image_url')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_images');
        Schema::connection($this->connection)->dropIfExists('drop_images');
    }
};
