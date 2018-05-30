<?php
    /**
     * Created by PhpStorm.
     * User: manoj
     * Date: 30/5/18
     * Time: 11:34 AM
     */

namespace App\Http\Controllers\CustomTraits;

use ExponentPhpSDK\Expo;
use Illuminate\Support\Facades\Log;
use NotificationChannels\ExpoPushNotifications\ExpoChannel;
use NotificationChannels\ExpoPushNotifications\ExpoMessage;
use Illuminate\Notifications\Notification;

trait NotificationTrait{

    public function sendNotification($notifiable){
        try{

Log::info('inside notification');
/*            ExpoMessage::create()
                ->badge(1)
                ->enableSound()
                ->body("Hi");
            $this->routeNotificationForExpoPushNotifications();*/



// You can quickly bootup an expo instance
Log::info(json_encode($notifiable));
            $expo = Expo::normalSetup();
Log::info('after setup');
// Subscribe the recipient to the server
$ids[0] = $notifiable['id'];
Log::info('after ids');
            $expo->subscribe($notifiable['id'], $notifiable['expo_token']);
Log::info('subscribe');
// Build the notification data
            $notification = ['body' => 'Hello World!', 'data' => ['message' => 'my message']];

// Notify an interest with a notification
Log::info('before notification');
            $expo->notify($ids, $notification);
Log::info('after noti');
            return true;
Log::info('after notification');
        }catch(\Exception $e){
            $data = [
                'action' => 'Send Push Notification',
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
            return null;
        }

    }
}
