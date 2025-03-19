<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->dropForeign(['company_id']); // 如果有外鍵，先刪除外鍵
            $table->dropColumn('company_id'); // 然後刪除 company_id 欄位
        });
    }

    public function down()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); // 重新加入 company_id
        });
    }
};
