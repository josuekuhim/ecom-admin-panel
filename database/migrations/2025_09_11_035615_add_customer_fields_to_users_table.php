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
        Schema::table('users', function (Blueprint $table) {
            // Customer specific fields
            $table->string('phone')->nullable()->after('email');
            $table->date('birth_date')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('birth_date');
            
            // Address fields
            $table->string('address')->nullable()->after('gender');
            $table->string('address_number')->nullable()->after('address');
            $table->string('complement')->nullable()->after('address_number');
            $table->string('neighborhood')->nullable()->after('complement');
            $table->string('city')->nullable()->after('neighborhood');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->string('country')->default('BR')->after('zip_code');
            
            // Customer preferences and metadata
            $table->json('clerk_metadata')->nullable()->after('clerk_user_id');
            $table->boolean('marketing_emails')->default(true)->after('clerk_metadata');
            $table->timestamp('first_login_at')->nullable()->after('marketing_emails');
            $table->timestamp('last_login_at')->nullable()->after('first_login_at');
            $table->enum('customer_type', ['individual', 'business'])->default('individual')->after('last_login_at');
            $table->string('document_type')->nullable()->after('customer_type'); // CPF, CNPJ
            $table->string('document_number')->nullable()->after('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'birth_date', 'gender',
                'address', 'address_number', 'complement', 'neighborhood', 
                'city', 'state', 'zip_code', 'country',
                'clerk_metadata', 'marketing_emails', 'first_login_at', 
                'last_login_at', 'customer_type', 'document_type', 'document_number'
            ]);
        });
    }
};
