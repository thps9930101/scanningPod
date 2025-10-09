<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClickTimesRemind extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // 這裡是固定值，例如剩下 3 次
        $times = 3;

        return (new MailMessage)
            ->subject('照相艙拍攝完成通知')
            ->line('照相艙已經拍攝完成，請點擊以下連結來觀看模型。')
            ->action('點我觀看模型', "https://www.bing.com/?scope=web&cc=TW&FORM=ANNTH1&pc=U531") // 按鈕
            ->line('如果您無法點擊按鈕，請複製以下網址貼到瀏覽器：')
            ->line("https://www.bing.com/?scope=web&cc=TW&FORM=ANNTH1&pc=U531"); // 純文字備用網址
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}