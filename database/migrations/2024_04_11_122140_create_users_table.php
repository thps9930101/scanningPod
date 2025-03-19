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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('account')->unique();
            $table->string('name');
            $table->string('email');
            //$table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            // $table->string('address')->nullable();
            // $table->string('fax')->nullable();
            // $table->integer('download_time')->default(0);
            //user types tiny integer
            //$table->boolean('is_admin')->default(false);
            //confirm code
            $table->string('confirm_code')->nullable();
            //confirm code expire time
            $table->dateTime('confirm_code_expired_at')->nullable();

            //belong to which store nullable foreign constraint delete set null
            //$table->foreignId('store_id')->nullable();
            //user is admin in which store
            //$table->boolean('is_store_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
