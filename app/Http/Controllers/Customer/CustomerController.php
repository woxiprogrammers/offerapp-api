<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Customer;

use Cornford\Googlmapper\Facades\MapperFacade as Mapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;
use Laravel\Lumen\Routing\Controller as BaseController;

class CustomerController extends BaseController
{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getLocation(Request $request){
        try{
            $coordinates = $request['coords'];
            $latlng = implode(",", [$coordinates['latitude'], $coordinates['longitude']]);
            $apiKey = urlencode(env('GOOGLE_API_KEY'));

            $response = Curl::to('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latlng.'&key='.$apiKey)
                              ->post();

            $result = json_decode($response);
            $address = $result->results[0]->formatted_address;
            $splitAddress = explode(',', $address);
            $shortAddress = $splitAddress[0];
            if (count($splitAddress)>2){
                $splitAddressCount = count($splitAddress)-3;
            }else{
                $splitAddressCount = count($splitAddress);
            }

            for ($iterator = 1; $iterator < $splitAddressCount; ++$iterator) {
                $shortAddress = $shortAddress.','.$splitAddress[$iterator];
            }

            $data = [
                'locationName' => $shortAddress,
                'status' => 200
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Location',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

    public function setLocation(Request $request){
        try{

            $address = $request['locationName'];

            $location = Mapper::location($address);
            $latitude = $location->getLatitude();
            $longitude = $location->getLongitude();

            $data = [
                'locationName' => $address,
                'coords' => [
                    'latitude' => $latitude,
                    'lonitude' => $longitude
                ],
                'status' => 200
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Set Location',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }
}