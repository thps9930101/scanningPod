<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineWebhookController extends Controller
{
    // 入口：LINE 會 POST 事件到這裡
    public function handle(Request $request)
    {
        // 1) 驗簽：一定要用「原始 body」
        $signature = $request->header('X-Line-Signature');
        $body      = $request->getContent(); // raw body
        if (!$this->isValidSignature($body, $signature)) {
            return response('Invalid signature', 400);
        }

        // 2) 解析事件
        $json   = json_decode($body, true);
        $events = $json['events'] ?? [];

        // 3) 處理每個事件
        foreach ($events as $event) {
            $type = $event['type'] ?? null;

            // 使用者掃 QR 加好友 → follow 事件
            if ($type === 'follow') {
                $this->bot()->replyText($event['replyToken'], '歡迎加入！這是一則測試訊息 ✅');
                continue;
            }

            // 一般文字訊息 → Echo
            if ($type === 'message' && ($event['message']['type'] ?? null) === 'text') {
                $text = $event['message']['text'] ?? '';
                $this->bot()->replyText($event['replyToken'], '你說：' . $text);
                continue;
            }
        }

        // 4) 盡快回 200，避免 replyToken 逾時
        return response()->json(['ok' => true]);
    }

    // 驗簽（X-Line-Signature = base64(HMAC_SHA256(rawBody, channelSecret)))
    private function isValidSignature(string $body, ?string $signature): bool
    {
        if (!$signature) return false;
        $secret = config('services.line.channel_secret');
        $hash   = base64_encode(hash_hmac('sha256', $body, $secret, true));
        return hash_equals($hash, $signature);
    }

    // 取得 LINEBot 實例（帶 token / secret）
    private function bot(): LINEBot
    {
        $token   = config('services.line.channel_access_token');
        $secret  = config('services.line.channel_secret');
        $client  = new CurlHTTPClient($token);
        return new LINEBot($client, ['channelSecret' => $secret]);
    }
}
