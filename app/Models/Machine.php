<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    use HasFactory;

    protected $table = 'machines'; // 关联的资料表名称

    protected $fillable = [
        'name',
        'type',
        'description',
        'camera',
        'status' //0: 未使用 1: 使用中 2: 故障
    ];
}
