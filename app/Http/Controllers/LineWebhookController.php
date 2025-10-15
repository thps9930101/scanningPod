<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
class LineWebhookController extends Controller
{
    /**
     * POST /api/line/register-and-push
     * body: { "uid":"Uxxxxxxxx", "msg":"你好", "link":"https://example.com" }
     */
    public function registerAndPush(Request $req)
    {
        $rid = (string) Str::uuid(); // 方便追蹤一筆請求
        $uid  = trim((string) $req->input('uid'));
        $msg  = (string) $req->input('msg', '');
        $link = (string) $req->input('link', '');

        // 進站即記錄（遮蔽 UID）
        $maskedUid = $uid ? substr($uid, 0, 4) . '***' . substr($uid, -4) : null;
        Log::info('[registerAndPush] IN', [
            'rid' => $rid,
            'uid' => $maskedUid,
            'has_msg' => $msg !== '',
            'has_link' => $link !== '',
            'ip' => $req->ip(),
            'ua' => $req->userAgent(),
        ]);

        try {
            if ($uid === '') {
                Log::warning('[registerAndPush] missing uid', ['rid' => $rid, 'ip' => $req->ip()]);
                return response()->json(['ok' => false, 'msg' => 'missing uid'], 422);
            }

            // 正規化 link
            if ($link !== '' && !preg_match('~^https?://~i', $link)) {
                $link = 'https://' . $link;
            }

            // 組訊息
            $messages = [];
            if ($msg !== '') {
                $messages[] = ['type' => 'text', 'text' => $msg];
            }
            if ($link !== '') 
            {
                $messages[] = [
                    'type' => 'flex',
                    'altText' => '查看連結',
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'md',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '開啟連結',
                                    'weight' => 'bold',
                                    'size' => 'lg',
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $link,
                                    'size' => 'sm',
                                    'color' => '#666666', // ← 改成 6 碼
                                    'wrap' => true,
                                ],
                            ],
                        ],
                        'footer' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'md',
                            'flex' => 0,
                            'contents' => [[
                                'type' => 'button',
                                'style' => 'primary',
                                'action' => ['type' => 'uri', 'label' => '前往', 'uri' => $link],
                            ]],
                        ],
                    ],
                ];
            }

            if (empty($messages)) {
                Log::warning('[registerAndPush] empty messages', ['rid' => $rid, 'uid' => $maskedUid]);
                return response()->json(['ok' => false, 'msg' => 'msg/link 需擇一提供'], 422);
            }

            // 呼叫 LINE Push API
            $res = \Illuminate\Support\Facades\Http::withToken(config('services.line.channel_access_token'))
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to'       => $uid,
                    'messages' => $messages,
                ]);

            if ($res->failed()) {
                Log::error('[registerAndPush] LINE push failed', [
                    'rid'    => $rid,
                    'uid'    => $maskedUid,
                    'status' => $res->status(),
                    'body'   => $res->json(), // 失敗時記 body 以利排查
                ]);
                return response()->json([
                    'ok' => false,
                    'msg' => 'push_failed',
                    'status' => $res->status(),
                    'body' => $res->json(),
                    'rid' => $rid,
                ], 500);
            }

            Log::info('[registerAndPush] OK', [
                'rid' => $rid,
                'uid' => $maskedUid,
                'status' => $res->status(),
            ]);

            return response()->json([
                'ok'     => true,
                'status' => $res->status(),
                'body'   => $res->json(),
                'rid'    => $rid,
            ]);

        } catch (Throwable $e) {
            // 捕捉所有 500 類錯誤：網路、設定、意外例外
            Log::error('[registerAndPush] EXCEPTION', [
                'rid' => $rid,
                'uid' => $maskedUid,
                'ip'  => $req->ip(),
                'ua'  => $req->userAgent(),
                'msg' => $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
                'trace'=> $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok'   => false,
                'msg'  => 'server_error',
                'rid'  => $rid,      // 回給前端一個可對應的追蹤碼
            ], 500);
        }
    }
}
