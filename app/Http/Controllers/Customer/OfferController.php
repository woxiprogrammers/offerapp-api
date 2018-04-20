<?php
/**
 * Created by PhpStorm.
 * User: arvind
 * Date: 18/4/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Customer;

use App\Category;
use App\Customer;
use App\CustomerOfferDetail;
use App\Http\Controllers\CustomTraits\OfferTrait;
use App\OfferStatus;
use App\Offer;
use App\OfferImage;
use App\OfferType;
use App\ReachTime;
use App\SellerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Ixudra\Curl\Facades\Curl;
use Laravel\Lumen\Routing\Controller as BaseController;

class OfferController extends BaseController
{
    use OfferTrait;
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

    public function nearByOffer(Request $request){

        try{
            $currentPage = Input::get('page', 1)-1;
            $near_by_zipcode = $request['zipcode'];
            $offertype_id = OfferType::where('slug',$request['offerTypeSlug'])->pluck('id')->first();
            $category_id = Category::where('slug',$request['categorySlug'])->pluck('id')->first();

            $near_by_seller_addresses = SellerAddress::where('zipcode', $near_by_zipcode)->pluck('id')->all();
            $near_by_offer = array();
            $iterator = 0;

            foreach ($near_by_seller_addresses as  $near_by_seller_address){
                $offers = Offer::where('seller_address_id',$near_by_seller_address)
                                ->where('offer_type_id', $offertype_id)
                                ->where('category_id', $category_id)
                                ->get();
                foreach ($offers  as $offer){
                    $near_by_offer[$iterator]['offerId'] = $offer->id;
                    $near_by_offer[$iterator]['offerName'] = $offer->offerType->name;
                    $near_by_offer[$iterator]['offerPic'] = env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$offer->offerImages->first()->name;
                    $near_by_offer[$iterator]['sellerInfo'] = $offer->sellerAddress->seller->user->first_name.' '.$offer->sellerAddress->seller->user->last_name;
                    $valid_to = $offer->valid_to;
                    $near_by_offer[$iterator]['offerExpiry']= date('d F, Y',strtotime($valid_to));
                    $iterator++;
                }
            }
            $pagedData = array_slice($near_by_offer, $currentPage * $this->perPage, $this->perPage);
            $data = [
                'records' => $pagedData,
                'pagination' => [
                    'page' => $currentPage +1 ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($pagedData),
                    'totalCount' => count($near_by_offer),
                ],
            ];


        }catch (\Exception $e){
            $data =[
                'parameter' => $request,
                'action' => 'nearByOffer',
                'errorMessage' => $e->getMessage()
            ];
        }
        return response()->json($data);

    }

    public function getDistanceBetween($origin, $destination ,$unit = 'km', $decimals = 2)
    {
        $point1 = [
            "lat" => 18.5482895,
            "lng" => 73.7935478
        ];

        $point2 = [
            "lat" => 18.5250051,
            "lng" => 73.7004978
        ];
        // Calculate the distance in degrees using Hervasine formula
        $degrees = $this->calcDistance($origin, $destination);
        // Convert the distance in degrees to the chosen unit (kilometres, miles or nautical miles)
        switch ($unit) {
            case 'km':
                // 1 degree = 111.13384 km, based on the average diameter of the Earth (12,735 km)
                $distance = $degrees * 111.13384;
                break;
            case 'mi':
                // 1 degree = 69.05482 miles, based on the average diameter of the Earth (7,913.1 miles)
                $distance = $degrees * 69.05482;
                break;
            case 'nmi':
                // 1 degree = 59.97662 nautic miles, based on the average diameter of the Earth (6,876.3 nautical miles)
                $distance = $degrees * 59.97662;
        }
        return $distance;
    }

    private function calcDistance($point1, $point2)
    {
        return rad2deg(acos((sin(deg2rad($point1['latitude'])) *
                sin(deg2rad($point2['latitude']))) +
            (cos(deg2rad($point1['latitude'])) *
                cos(deg2rad($point2['latitude'])) *
                cos(deg2rad($point1['longitude'] - $point2['longitude'])))));
    }

    public function getDistanceByGoogleApi(Request $request){
        try{

            $origin = $request['origin'];
            $destination = $request['destination'];

            $origin = implode(",", [$origin['latitude'], $origin['longitude']]);
            $destination = implode(",", [$destination['latitude'], $destination['longitude']]);
            $apiKey = urlencode(env('GOOGLE_API_KEY'));

            $data = Curl::to('https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$origin.'&destinations='.$destination.'&key='.$apiKey)
                        ->post();

        }catch(\Exception $e){

            $data = [
                'action' => 'Get Distance Using Google Api',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
        }
        return $data;
    }



}