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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id'); // 新增 company_id 字段
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null'); // 设定外键关联
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['company_id']); // 删除外键
            $table->dropColumn('company_id'); // 删除字段
        });
    }
};
