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
        Schema::table('product_images', function (Blueprint $table) {
            $table->binary('image_data')->nullable()->after('image_url');
            $table->string('mime_type')->nullable()->after('image_data');
            $table->string('original_filename')->nullable()->after('mime_type');
            $table->integer('file_size')->nullable()->after('original_filename');
        });

        Schema::table('drop_images', function (Blueprint $table) {
            $table->binary('image_data')->nullable()->after('image_url');
            $table->string('mime_type')->nullable()->after('image_data');
            $table->string('original_filename')->nullable()->after('mime_type');
            $table->integer('file_size')->nullable()->after('original_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'mime_type', 'original_filename', 'file_size']);
        });

        Schema::table('drop_images', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'mime_type', 'original_filename', 'file_size']);
        });
    }
};
