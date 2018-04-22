<?php
/**
 * Created by PhpStorm.
 * User: harsha
 * Date: 25/3/18
 * Time: 10:40 PM
 */

namespace App\Http\Controllers\Seller;

use App\Floor;
use App\Seller;
use App\SellerAddress;
use Cornford\Googlmapper\Facades\MapperFacade as Mapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;


class SellerController extends BaseController
{
    public function __construct() {
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }
    public function addSellerAddress(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $search_address = $request['shopName'].' '.$request['address'];
            $detail_address = $this->sellerDetailAddress($search_address);

            $seller_id = Seller::where('user_id', Auth::user()->id)->pluck('id')->first();
            $seller_address = SellerAddress::where('seller_id', $seller_id)->first();
            if (!isset($seller_address)){
                $seller_address = SellerAddress::create([
                    'seller_id' => $seller_id,
                    'shop_name' => $request['shopName'],
                    'landline' => $request['landline'],
                    'address' => $detail_address['detailAddress']['address'],
                    'floor_id' => Floor::where('slug', $request['floorSlug'])->pluck('id')->first(),
                    'zipcode' => $detail_address['detailAddress']['zipcode'],
                    'city' => $detail_address['detailAddress']['city'],
                    'state' => $detail_address['detailAddress']['state'],
                    'latitude' => $detail_address['detailAddress']['latitude'],
                    'longitude' => $detail_address['detailAddress']['longitude'],
                    'is_active' => true,
                ]);

                $data = [
                    'sellerAddress' => $seller_address,
                    'sellerAddressAdd' => true,
                ];
            }else{
                $data = [
                    'message' => 'Please Enter a Valid Selller\'s Detail',
                    'sellerAddressAdd' => false,
                ];
            }

        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data =[
                'action' => 'Add Seller Address Detail',
                'exception' => $e->getMessage(),
                'params' => $request->all(),
                'sellerAddressAdded' => false,
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);

    }

    public function updateSellerAddress(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $search_address = $request['shopName'].' '.$request['address'];
            $detail_address = $this->sellerDetailAddress($search_address);

            $seller_id = Seller::where('user_id', Auth::user()->id)->pluck('id')->first();
            $seller_address = SellerAddress::where('seller_id', $seller_id)->first();
            if (isset($seller_address)){
                $seller_address->shop_name = $request['shopName'];
                $seller_address->landline = $request['landline'];
                $seller_address->address = $detail_address['detailAddress']['address'];
                $seller_address->floor_id = Floor::where('slug', $request['floorSlug'])->pluck('id')->first();
                $seller_address->zipcode = $detail_address['detailAddress']['zipcode'];
                $seller_address->city = $detail_address['detailAddress']['city'];
                $seller_address->state = $detail_address['detailAddress']['state'];
                $seller_address->latitude = $detail_address['detailAddress']['latitude'];
                $seller_address->longitude = $detail_address['detailAddress']['longitude'];
                $seller_address->save();

                $data = [
                    'sellerAddress' => $seller_address,
                    'sellerAddressAdded' => true,
                ];
            }else{
                $data = [
                    'message' => 'Please Enter a Valid Selller\'s Detail',
                    'sellerAddressUpdated' => false,
                ];
            }

        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data =[
                'action' => 'Add Seller Address Detail',
                'exception' => $e->getMessage(),
                'params' => $request->all(),
                'sellerAddressAdded' => false,
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);


    }

    public function sellerDetailAddress($search_address){
        try{

            $location = Mapper::location($search_address);
            $address = '';
            $city = '';
            $state = '';
            $google_addresses = explode(',',$location->getAddress());
            $size_of_google_addresses = sizeof($google_addresses);
            foreach ($google_addresses as $key => $google_address){
                if($key < $size_of_google_addresses-3){
                    $address = $address.', '.$google_address;
                }
                elseif ($key == $size_of_google_addresses-3){
                    $city = $google_address;
                }
                elseif ($key == $size_of_google_addresses-2){
                    $state = $google_address;
                }
            }
            $data = [
                'detailAddress' => [
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zipcode' => $location->getPostalCode(),
                    'latitude' => $location->getLatitude(),
                    'longitude' => $location->getLongitude(),
                ]
            ];

        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data =[
                'action' => ' Seller Detail Address ',
                'exception' => $e->getMessage(),
                'params' => $search_address
            ];
            Log::critical(json_encode($data));

        }
        return $data;
    }
}