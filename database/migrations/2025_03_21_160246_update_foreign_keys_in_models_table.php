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
            // 1. 先刪除舊的 user_id 外鍵（如果存在）
            $table->dropForeign(['user_id']); 
            $table->dropColumn('user_id'); 

            // 2. 新增 order_id 欄位並建立外鍵
            $table->unsignedBigInteger('order_id')->after('id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('models', function (Blueprint $table) {
            // 1. 刪除 order_id 外鍵
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');

            // 2. 恢復 user_id 欄位
            $table->unsignedBigInteger('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
