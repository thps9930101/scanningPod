<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarks', function (Blueprint $table) {
            // 刪除舊的外鍵和欄位
            $table->dropForeign(['model_id']);
            $table->dropColumn('model_id');

            // 新增 order_id 外鍵
            $table->unsignedBigInteger('order_id')->after('id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('remarks', function (Blueprint $table) {
            // 回復刪除的欄位
            $table->unsignedBigInteger('model_id')->after('id');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');

            // 刪除 order_id
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
