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
use App\Group;
use App\GroupCustomer;
use App\GroupMessage;
use App\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class WishListController extends BaseController
{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }


    public function getWishListOffers(Request $request){
        try{
            $perPage = 5;
            $user_id = $request['userId'];

            $offers[0]['offerId'] = '';
            $offers[0]['offerName'] = '';
            $offers[0]['offerPic'] = '';
            $offers[0]['sellerInfo'] = '';
            $offers[0]['offerExpiry']= '';

            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $customer_offers = CustomerOfferDetail::where('customer_id',$customer_id)
                                ->where('is_wishlist',true)
                                ->paginate($perPage);

            foreach ($customer_offers as $key => $customer_offer){
                    $offers[$key]['offerId'] = $customer_offer->offer->id;
                    $offers[$key]['offerName'] = $customer_offer->offer->offerType->name;
                    $offers[$key]['offerPic'] = env('IMAGE_PATH').$customer_offer->offer->offerImages->first()->name;
                    $offers[$key]['sellerInfo'] = $customer_offer->offer->sellerAddress->seller->user->first_name.' '.$customer_offer->offer->sellerAddress->seller->user->last_name;
                    $valid_to = $customer_offer->offer->valid_to;
                    $offers[$key]['offerExpiry']= date('d F, Y',strtotime($valid_to));
            }
            $data = [
                'records' => $offers,
                'pagination' => [
                    'page' => $customer_offers->currentPage(),
                    'perPage' => $perPage,
                    'pageCount' => $customer_offers->count(),
                    'totalCount' => $customer_offers->total(),
                ],
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Customer Wish List Offers',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }


    public function removeWishList(Request $request){
        try{
            $user_id = $request['userId'];
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


    public function addWishList(Request $request){
        try{
            $user_id = $request['userId'];
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


}