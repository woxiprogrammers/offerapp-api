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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class InterestedOfferController extends BaseController
{

    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getInterestedOffers(Request $request){
        try{
            $perPage = 5;
            $offer_status = 'interested';
            $user_id = $request['userId'];

            $offers[0]['offerId'] = '';
            $offers[0]['offerName'] = '';
            $offers[0]['offerPic'] = '';
            $offers[0]['sellerInfo'] = '';
            $offers[0]['offerExpiry']= '';
            $offers[0]['grabCode']= '';


            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();
            $offer_status_id = OfferStatus::where('slug',$offer_status)->pluck('id')->first();
            $customer_offers = CustomerOfferDetail::where('customer_id',$customer_id)
                                ->where('offer_status_id',$offer_status_id)
                                ->paginate($perPage);

            foreach ($customer_offers as $key => $customer_offer){
                    $offers[$key]['offerId'] = $customer_offer->offer->id;
                    $offers[$key]['offerName'] = $customer_offer->offer->offerType->name;
                    $offers[$key]['offerPic'] = env('IMAGE_PATH').$customer_offer->offer->offerImages->first()->name;
                    $offers[$key]['sellerInfo'] = $customer_offer->offer->sellerAddress->seller->user->first_name.' '.$customer_offer->offer->sellerAddress->seller->user->last_name;
                    $valid_to = $customer_offer->offer->valid_to;
                    $offers[$key]['offerExpiry']= date('d F, Y',strtotime($valid_to));
                    $offers[$key]['grabCode'] = $customer_offer->offer_code;
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
                'action' => 'Get Interseted Offers',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return response()->json($data);
    }

    public function setInterestedOffers(Request $request){
        try {
            $user_id = $request['userId'];
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
                    'CustomerOfferDetail' => $customer_offer_detail,
                ];
            } else {

            CustomerOfferDetail::create([
                'customer_id' => $customer_id,
                'offer_id' => $offer_id,
                'offer_status_id' => $offer_status_id,
                'reach_time' => $reach_time,
                'offer_code' => $grab_code,
            ]);
            }
             $data = [
                 'AddedToInterested' => true,
                 'CustomerOfferDetail' => $customer_offer_detail,
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

}