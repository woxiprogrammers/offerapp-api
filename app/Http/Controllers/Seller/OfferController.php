<?php
/**
 * Created by PhpStorm.
 * User: harsha
 * Date: 25/3/18
 * Time: 10:40 PM
 */

namespace App\Http\Controllers\Seller;

use App\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;


class OfferController extends BaseController
{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getOfferListing(Request $request){
        try{
            $user = Auth::user();
            $seller = Seller::where('user_id',$user['id'])->first();
            $sellerAddresses = $seller->sellerAddress;
            $iterator = 0;
            $offerList = array();
            foreach($sellerAddresses as $key => $sellerAddress){
                $offers = $sellerAddress->offer->orderBy('created_at','desc');
                foreach ($offers as $key2 => $offer){
                    $offerList[$iterator]['seller_id'] = $seller['id'];
                    $offerList[$iterator]['seller_name'] = $user['first_name'].' '.$user['last_name'];
                    $offerList[$iterator]['seller_address_id'] = $sellerAddress['id'];
                    $offerList[$iterator]['offer_id'] = $offer['id'];
                    $offerList[$iterator]['offer_type_id'] = $offer['offer_type_id'];
                    $offerList[$iterator]['offer_type_name'] = $offer->offerType->name;
                    $offerList[$iterator]['offer_status_id'] = $offer['offer_status_id'];
                    $offerList[$iterator]['offer_status_name'] = $offer->offerStatus->name;
                    $offerList[$iterator]['offer_description'] = $offer->description;
                    $offerList[$iterator]['valid_from'] = $offer['valid_from'];
                    $offerList[$iterator]['valid_to'] = $offer['valid_to'];
                    $iterator++;
                }
            }
            $data['offer_list'] = $offerList;
            $status = 200;
            $message = 'Success';

        }catch(\Exception $e){
            $status = 500;
            $message = 'fail';
            $data = [
                'action' => 'Get Seller Side offer Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response , $status);
    }


}