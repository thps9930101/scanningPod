<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarks', function (Blueprint $table) {
            // 刪除舊的 company_id 外鍵和欄位
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            // 新增 staff_id 外鍵
            $table->unsignedBigInteger('staff_id')->after('id');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('remarks', function (Blueprint $table) {
            // 回復 company_id 欄位
            $table->unsignedBigInteger('company_id')->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // 刪除 staff_id
            $table->dropForeign(['staff_id']);
            $table->dropColumn('staff_id');
        });
    }
};
