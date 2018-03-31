<?php
/**
 * Created by PhpStorm.
 * User: harsha
 * Date: 25/3/18
 * Time: 10:40 PM
 */

namespace App\Http\Controllers\Seller;

use App\OfferStatus;
use App\Seller;
use App\Offer;
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
                if($request['status_slug'] == 'all'){
                    $offers = $sellerAddress->offer;
                }else{
                    $offerStatusId = OfferStatus::where('slug',$request['status_slug'])->pluck('id')->first();
                    $offers = $sellerAddress->offer->where('offer_status_id',$offerStatusId);
                }
                foreach ($offers as $key2 => $offer){
                    $offerList[$iterator]['offer_id'] = $offer['id'];
                    $offerList[$iterator]['seller_address_id'] = $sellerAddress['id'];
                    $offerList[$iterator]['offer_type_id'] = $offer['offer_type_id'];
                    $offerList[$iterator]['offer_type_name'] = $offer->offerType->name;
                    $offerList[$iterator]['offer_status_id'] = $offer['offer_status_id'];
                    $offerList[$iterator]['offer_status_name'] = $offer->offerStatus->name;
                    $offerList[$iterator]['offer_description'] = $offer->description;
                    $offerList[$iterator]['valid_from'] = $offer['valid_from'];
                    $offerList[$iterator]['valid_to'] = $offer['valid_to'];
                    $offerList[$iterator]['wishlist_count'] = 1;
                    $offerList[$iterator]['interested_count'] = 1;
                    $offerList[$iterator]['grabbed_count'] = 1;
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

    public function getOfferDetail(Request $request){
        try{
            $offerId = $request['offer_id'];
            $offer = Offer::where('id' , $offerId)->first();
            $offerList = array();
            $offerList['offer_id'] = $offer['id'];
            $sellerAddress = $offer-> sellerAddress;
            $offerList['seller_address_id'] = $sellerAddress->id;
            $offerList['floor_no'] = $sellerAddress->floor->no;
            $offerList['seller_address'] = $sellerAddress->shop_name.' '.$sellerAddress->city;
            $offerList['full_seller_address'] = $sellerAddress->floor->no.' '.$sellerAddress->shop_name.' '.$sellerAddress->address.' '.$sellerAddress->city.' '.$sellerAddress->state.' '.$sellerAddress->zipcode;
            // $offerList['offer_images'] = $offer->offerImages->name;
            $offerList['offer_type_name'] = $offer->offerType->name;
            $offerList['offer_status_name'] = $offer->offerStatus->name;
            $offerList['offer_description'] = ($offer->description == null) ? '' : $offer->description;
            $valid_from = $offer->valid_from;
            $valid_to = $offer->valid_to;
            $offerList['start_date']= date('d F, Y',strtotime($valid_from));
            $offerList['end_date']= date('d F, Y',strtotime($valid_to));
            $data['offer_detail']  = $offerList;
            $message = 'Success';
            $status = 200;
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Seller Offer Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'message' => $message,
            'data'=>$data
        ];
        return response()->json($response,$status);
    }


}