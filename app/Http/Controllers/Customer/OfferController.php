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
use App\OfferStatus;
use App\Offer;
use App\OfferImage;
use App\ReachTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class OfferController extends BaseController
{
    protected $perPage = 5;

    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function offerListing(Request $request){
        try{
            $offer_status = $request['offerStatus'];
            $user_id = Auth::user()->id;
            $offers = array();
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();
                if($offer_status == 'wishlist'){
                    $customer_offers = CustomerOfferDetail::where('customer_id',$customer_id)
                                        ->where('is_wishlist',true)
                                        ->paginate($this->perPage);
                }elseif ($offer_status == 'interested'){
                $offer_status = 'interested';
                $offer_status_id = OfferStatus::where('slug',$offer_status)->pluck('id')->first();
                $customer_offers = CustomerOfferDetail::where('customer_id',$customer_id)
                                    ->where('offer_status_id',$offer_status_id)
                                    ->paginate($this->perPage);
                }
            foreach ($customer_offers as $key => $customer_offer){
                $offers[$key]['offerId'] = $customer_offer->offer->id;
                $offers[$key]['offerName'] = $customer_offer->offer->offerType->name;
                $offers[$key]['offerPic'] = env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$customer_offer->offer->offerImages->first()->name;
                $offers[$key]['sellerInfo'] = $customer_offer->offer->sellerAddress->seller->user->first_name.' '.$customer_offer->offer->sellerAddress->seller->user->last_name;
                $valid_to = $customer_offer->offer->valid_to;
                $offers[$key]['offerExpiry']= date('d F, Y',strtotime($valid_to));
                $offers[$key]['grabCode'] = $customer_offer->offer_code;
            }
            $data = [
                'records' => $offers,
                'pagination' => [
                    'page' => $customer_offers->currentPage(),
                    'perPage' => $this->perPage,
                    'pageCount' => $customer_offers->count(),
                    'totalCount' => $customer_offers->total(),
                ],
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Customer Offers Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);

    }

    public function getInterestedOfferDetail(Request $request){
        try{

            $user_id = Auth::user()->id;
            $offer_id = $request['offerId'];

            $offers = array();
            $imageList = array();
            $loadQueue = array();

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
            $images = OfferImage::where('offer_id',$offer_id)->get();

            foreach($images as $key => $image){
                $imageList[$key] = env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$image->name;
                $loadQueue[$key] = 0;
            }
            $data = [
                'offerDetail' => $offers,
                'imageList' => $imageList,
                'loadQueue' => $loadQueue
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Interested Offer Detail ',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

    public function addToInterest(Request $request){
        try {
            $user_id = Auth::user()->id;
            $offer_id = $request['offerId'];
            $reach_time = $request['selectedTime'];
            $grab_code = str_random(5);
            $offer_status = 'interested';
            $offer_status_id = OfferStatus::where('slug', $offer_status)->pluck('id')->first();
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();
            $customer_offer_detail = CustomerOfferDetail::where('customer_id', $customer_id)
                ->where('offer_id', $offer_id)
                ->first();
            if (isset($customer_offer_detail)) {
                $customer_offer_detail->offer_status_id = $offer_status_id;
                $customer_offer_detail->save();
                $data = [
                    'AddedToInterested' => true,
                ];
            } else {

                $reach_time_id = ReachTime::where('slug', $reach_time)->pluck('id')->first();
                 CustomerOfferDetail::create([
                    'customer_id' => $customer_id,
                    'offer_id' => $offer_id,
                    'offer_status_id' => $offer_status_id,
                    'reach_time_id' => $reach_time_id,
                    'offer_code' => $grab_code,
                ]);
            }
            $data = [
                'AddedToInterested' => true,
            ];

        }catch(\Exception $e){

            $data = [
                'action' => 'Set Interested Offers',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

    public function addToWishlist(Request $request){
        try{
            $user_id = Auth::user()->id;
            $offer_id = $request['offerId'];
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $offer_status_id = Offer::where('id', $offer_id)->pluck('offer_status_id')->first();

            $customer_offer_detail = CustomerOfferDetail::where('customer_id',$customer_id)
                ->where('offer_id', $offer_id)->first();
            if(isset($customer_offer_detail)){

                $customer_offer_detail->is_wishlist = true;
                $customer_offer_detail->save();

            }else{
                CustomerOfferDetail::create([
                    'customer_id' => $customer_id,
                    'offer_id' => $offer_id,
                    'offer_status_id' => $offer_status_id,
                    'reach_time' => '',
                    'offer_code' => '',
                    'is_wishlist' => true,
                ]);
            }
            $data = [
                'addedToWishList' => true
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Add Offer to WishList',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

    public function removeFromWishlist(Request $request){
        try{
            $user_id = Auth::user()->id;
            $offer_id = $request['offerId'];
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $customer_offer_detail = CustomerOfferDetail::where('customer_id',$customer_id)
                ->where('offer_id', $offer_id)
                ->first();
            if(isset($customer_offer_detail)){

                $customer_offer_detail->is_wishlist = false;
                $customer_offer_detail->save();
                $data = [
                    'removed' => true
                ];
            }else{
                $data = [
                    'removed' => false
                ];
            }

        }catch(\Exception $e){

            $data = [
                'action' => 'Remove Offer from WishList',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }




}