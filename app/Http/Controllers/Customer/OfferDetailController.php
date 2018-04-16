<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Customer;

use App\Customer;
use App\CustomerOfferDetail;
use App\OfferImage;
use App\OfferStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class OfferDetailController extends BaseController
{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }
    public function getInterestedOfferDetail(Request $request){
        try{
            $user_id = $request['userId'];
            $offer_id = $request['offerId'];

            $offers['offerId'] = '';
            $offers['offerName'] = '';
            $offers['offerPic'] = '';
            $offers['sellerInfo'] = '';
            $offers['offerExpiry']= '';
            $offers['sellerNumber'] = '';
            $offers['offerLatitude'] = '';
            $offers['offerLongitude'] = '';
            $offers['offerDescription'] = '';
            $offers['addedToWishList'] = '';
            $offers['addedToInterested']= '';

            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $customer_offer = CustomerOfferDetail::where('customer_id',$customer_id)
                                ->where('offer_id',$offer_id)
                                ->first();

                    $offers['offerId'] = $customer_offer->offer->id;
                    $offers['offerName'] = $customer_offer->offer->offerType->name;
                    $offers['offerPic'] = env('IMAGE_PATH').$customer_offer->offer->offerImages->first()->name;
                    $offers['sellerInfo'] = $customer_offer->offer->sellerAddress->seller->user->first_name.' '.$customer_offer->offer->sellerAddress->seller->user->last_name;
                    $valid_to = $customer_offer->offer->valid_to;
                    $offers['offerExpiry']= date('d F, Y',strtotime($valid_to));
                    $offers['sellerNumber'] = $customer_offer->offer->sellerAddress->landline;
                    $offers['offerLatitude'] = $customer_offer->offer->sellerAddress->latitude;
                    $offers['offerLongitude'] = $customer_offer->offer->sellerAddress->longitude;
                    $offers['offerDescription'] = $customer_offer->offer->description;
                    $offers['addedToWishList'] = $customer_offer->is_wishlist;

                    $offer_status = OfferStatus::where('id',$customer_offer->offer_status_id)->pluck('slug')->first();
                    if($offer_status == 'interested'){
                        $offers['addedToInterested'] = true;

                    }else{
                        $offers['addedToInterested'] = false;

                    }
            $data =  $offers;
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Interested Offer Detail ',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }


    public function getOfferImages(Request $request){
        try{
            $offer_id = $request['offerId'];

            $imageList[0] = '';
            $loadQueue[0] = '';

            $images = OfferImage::where('offer_id',$offer_id)->get();
            foreach($images as $key => $image){
                $imageList[$key] = env('IMAGE_PATH').$image->name;
                $loadQueue[$key] = 0;
            }
            $data = [
                'imageList' => $imageList,
                'loadQueue' => $loadQueue
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Offer Images',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

}