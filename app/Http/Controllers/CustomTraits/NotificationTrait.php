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
/*            ExpoMessage::create()
                ->badge(1)
                ->enableSound()
                ->body("Hi");
            $this->routeNotificationForExpoPushNotifications();*/



// You can quickly bootup an expo instance
            $expo = Expo::normalSetup();

// Subscribe the recipient to the server
            $expo->subscribe($notifiable, $notifiable['expo_token']);

// Build the notification data
            $notification = ['body' => 'Hello World!', 'data' => ['message' => 'my message']];

// Notify an interest with a notification
            $expo->notify($notifiable, $notification);
            return true;
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