<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class order extends Model
{
    use HasFactory;  
    /**
     * 資料庫表的名稱
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * 資料庫表的主鍵
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 可以批量賦值的欄位
     *
     * @var array
     */
    protected $fillable = [
        'machine_id',
        'status',
        'company_id',
        'id'
    ];



    /**
     * 是否使用時間戳記
     *
     * @var bool
     */
    public $timestamps = true;
}
