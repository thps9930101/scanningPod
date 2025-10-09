<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class LineWebhookController extends Controller
{
    public function registerAndPush(Request $req)
    {
        $uid = $req->input('userId');
        $name = $req->input('name');
        $campaign = $req->input('campaign');

        if (!$uid) return response()->json(['ok'=>false,'msg'=>'missing userId'], 422);

        // 簡單入庫（若沒有資料表可省略這段）
        // DB::table('line_users')->updateOrInsert(
        //     ['user_id'=>$uid],
        //     ['display_name'=>$name, 'last_campaign'=>$campaign, 'updated_at'=>now(), 'created_at'=>now()]
        // );

        // 立刻推播一則訊息
        $res = Http::withToken(config('services.line.channel_access_token'))
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $uid,
                'messages' => [[
                    'type' => 'text',
                    'text' => '掃到了！這是一則測試訊息 ✅'
                ]]
            ]);

        return response()->json(['ok'=>$res->successful(), 'status'=>$res->status()]);
    }
}
