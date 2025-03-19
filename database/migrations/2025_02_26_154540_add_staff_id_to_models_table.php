<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
