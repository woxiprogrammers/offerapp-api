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
use Illuminate\Support\Facades\Log;
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
            $message = "Success";
            $status = 200;
            $data = array();
            $offer_status = $request['offerStatus'];
            $user = Auth::user();
            $user_id = $user['id'];
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
            }else{
                $customer_offers = array();
            }
            foreach ($customer_offers as $key => $customer_offer){
                $offer = $customer_offer->offer;
                $offers[$key]['offerId'] = $offer->id;
                $offers[$key]['offerName'] = $offer->offerType->name;

                if(count($offer->offerImages) > 0){
                    $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                    $sha1OfferId = sha1($offer->id);
                    $offers[$key]['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offer->offerImages->first()->name;
                }else{
                    $offers[$key]['offerPic'] = '/uploads/no_image.jpg';;
                }
                $sellerUser = $offer->sellerAddress->seller->user;
                $offers[$key]['sellerInfo'] = $sellerUser->first_name.' '.$sellerUser->last_name;
                $valid_to = $offer->valid_to;
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
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get Customer Offers Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);
    }

    public function getInterestedOfferDetail(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $user = Auth::user();
            $user_id = $user['id'];
            $offer_id = $request['offerId'];

            $offers = array();
            $imageList = array();
            $loadQueue = array();

            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $customer_offer = CustomerOfferDetail::where('customer_id',$customer_id)
                ->where('offer_id',$offer_id)
                ->first();
            $offer = $customer_offer->offer;
            $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
            $sha1OfferId = sha1($offer['id']);
            $offers['offerId'] = $offer->id;
            $offers['offerName'] = $offer->offerType->name;
            $offerImages = $customer_offer->offer->offerImages;
            if(count($offerImages) > 0){
                $offers['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offerImages->first()->name;
            }else{
                $offers['offerPic'] = '/uploads/no_image.jpg';
            }

            $seller = $offer->sellerAddress->seller;
            $offers['sellerInfo'] = $seller->user->first_name.' '.$seller->user->last_name;
            $valid_to = $offer->valid_to;
            $offers['offerExpiry']= date('d F, Y',strtotime($valid_to));
            $offers['sellerNumber'] = $offer->sellerAddress->landline;
            $offers['offerLatitude'] = $offer->sellerAddress->latitude;
            $offers['offerLongitude'] = $offer->sellerAddress->longitude;
            $offers['offerDescription'] = $offer->description;
            $offers['addedToWishList'] = $customer_offer->is_wishlist;

            $offer_status = OfferStatus::where('id',$customer_offer->offer_status_id)->pluck('slug')->first();
            if($offer_status == 'interested'){
                $offers['addedToInterested'] = true;
            }else{
                $offers['addedToInterested'] = false;
            }
            $images = OfferImage::where('offer_id',$offer_id)->get();
            if(count($images) > 0){
                foreach($images as $key => $image){
                    $imageList[$key] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$image->name;
                    $loadQueue[$key] = 0;
                }
            }

            $data = [
                'offerDetail' => $offers,
                'imageList' => $imageList,
                'loadQueue' => $loadQueue
            ];
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get Interested Offer Detail ',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);
    }

    public function addToInterest(Request $request){
        try {
            $message = "Success";
            $status = 200;
            $data = array();
            $user = Auth::user();
            $user_id = $user['id'];
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
            }else{
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
                'addedToInterested' => true,
            ];

        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Set Interested Offers',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);
    }

    public function addToWishlist(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $user = Auth::user();
            $user_id = $user['id'];
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
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Add Offer to WishList',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);
    }

    public function removeFromWishlist(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $user = Auth::user();
            $user_id = $user['id'];
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
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Remove Offer from WishList',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);
    }

    public function nearByOffer(Request $request){

        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $user = Auth::user();
            $user_id = $user['id'];
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
                    $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                    $sha1OfferId = sha1($offer->id);
                    if(count($offer->offerImages) > 0){
                        $near_by_offer[$iterator]['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offer->offerImages->first()->name;
                    }else{
                        $near_by_offer[$iterator]['offerPic'] = '/uploads/no_image.jpg';
                    }
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
            $message = "Fail";
            $status = 500;
            $data =[
                'parameter' => $request,
                'action' => 'nearByOffer',
                'errorMessage' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response,$status);

    }

    public function getDistanceBetween($origin, $destination ,$unit = 'km', $decimals = 2)
    {
        try{
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
        }catch (\Exception $e ){
            $data = [
                'action' => 'get Distance Between Origin And Destination',
                'exception' => $e->getMessage(),
                'params' => [
                    'origin' => $origin,
                    'destination' => $destination,
                ]
            ];
            Log::critical(json_encode($data));
        }

    }

    protected function calcDistance($point1, $point2)
    {
        try{
            return rad2deg(acos((sin(deg2rad($point1['latitude'])) *
                    sin(deg2rad($point2['latitude']))) +
                (cos(deg2rad($point1['latitude'])) *
                    cos(deg2rad($point2['latitude'])) *
                    cos(deg2rad($point1['longitude'] - $point2['longitude'])))));
        }catch (\Exception $e){
            $data = [
                'action' => 'Calculate Distance',
                'exception' => $e->getMessage(),
                'params' => [
                    'origin' => $point1,
                    'destination' => $point2,
                ]
            ];
            Log::critical(json_encode($data));
        }

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
            Log::critical(json_encode($data));

        }
        return $data;
    }



}