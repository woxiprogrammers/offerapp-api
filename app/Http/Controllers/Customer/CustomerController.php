<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class CustomerController extends BaseController
{
    public function getLocation(Request $request)
    {
        try{

            $apiKey = urlencode(env('GOOGLE_KEY'));
            $latlng = implode(',',$request['co-ordinates']);
            $data = array('key' => $apiKey);
            // Send the POST request with cURL
            $ch = curl_init('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latlng.'&');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            // Process your response here
            $address = json_decode($response);
            if(count($address->results) > 0){
                return $address->results[0]->formatted_address;
            }else{
                return '';
            }
        }catch(\Exception $e){
            dd($e->getMessage());

            $data = [
                'action' => 'Get Location',
                'exception' => $e->getMessage()
            ];
            Log::citical(json_encode($data));
        }
    }

}