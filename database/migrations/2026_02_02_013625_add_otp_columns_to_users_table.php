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
            $table->dropColumn(['verification_token', 'verification_token_expires_at']);
            $table->dropColumn(['reset_password_token', 'reset_password_expires_at']);
            
            // Add OTP columns
            $table->string('email_verification_code')->nullable();
            $table->timestamp('email_verification_code_expires_at')->nullable();
            $table->integer('email_verification_attempts')->default(0);
            
            $table->string('password_reset_code')->nullable();
            $table->timestamp('password_reset_code_expires_at')->nullable();
            $table->integer('password_reset_attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_token')->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->string('reset_password_token')->nullable();
            $table->timestamp('reset_password_expires_at')->nullable();
            
            $table->dropColumn([
                'email_verification_code',
                'email_verification_code_expires_at',
                'email_verification_attempts',
                'password_reset_code',
                'password_reset_code_expires_at',
                'password_reset_attempts'
            ]);
        });
    }
};
