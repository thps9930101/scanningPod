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
        Schema::create('remarks', function (Blueprint $table) {
            $table->id(); // 自增主键
            $table->unsignedBigInteger('company_id'); // 关联 companies 表的 ID
            $table->unsignedBigInteger('model_id'); // 关联 companies 表的 ID
            $table->text('remark')->nullable(); // 備註字段，允许为空
            $table->timestamps(); // created_at 和 updated_at

            // 外键约束，关联到 companies 表的 id 字段
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade'); // 当关联的公司被删除时，删除对应的备注

            $table->foreign('model_id')
                  ->references('id')
                  ->on('models')
                  ->onDelete('cascade'); // 关联的订单删除时删除对应的备注
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
};
