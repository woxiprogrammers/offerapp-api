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

    public function getCustomerOfferDetail(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = $imageList = $loadQueue = array();
            $user = Auth::user();

            $offer = Offer::where('id',$request['offerId'])->first();
            $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
            $sha1OfferId = sha1($offer['id']);
            $offers['offerId'] = $offer->id;
            $offers['offerName'] = $offer->offerType->name;
            $offerImages = $offer->offerImages;
            if(count($offerImages) > 0){
                $offers['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offerImages->first()->name;
                foreach($offerImages as $key => $image){
                    $imageList[$key] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$image->name;
                    $loadQueue[$key] = 0;
                }
            }else{
                $offers['offerPic'] = '/uploads/no_image.jpg';
                $imageList[0] = '/uploads/no_image.jpg';
                $loadQueue[0] = 0;
            }
            $seller = $offer->sellerAddress;
            $offers['sellerInfo'] = $seller->shop_name;
            $valid_to = $offer->valid_to;
            $offers['offerExpiry']= date('d F, Y',strtotime($valid_to));
            $offers['sellerNumber'] = $offer->sellerAddress->landline;
            $offers['offerLatitude'] = (double)$offer->sellerAddress->latitude;
            $offers['offerLongitude'] = (double)$offer->sellerAddress->longitude;
            $offers['offerDescription'] = $offer->description;
            $offers['sellerAddress'] = $seller->address;

            $customerId = Customer::where('user_id', $user['id'])->pluck('id')->first();
            $customerOffer = CustomerOfferDetail::where('customer_id',$customerId)
                ->where('offer_id',$offer['id'])
                ->first();
                if (count($customerOffer)>0){
                    $offers['addedToWishList'] = $customerOffer->is_wishlist;
                    $offer_status = OfferStatus::where('id',$customerOffer->offer_status_id)->pluck('slug')->first();
                    if($offer_status == 'interested'){
                        $offers['addedToInterested'] = true;
                    }else{
                        $offers['addedToInterested'] = false;
                    }
                }else{
                    $offers['addedToWishList'] = false;
                    $offers['addedToInterested'] = false;
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

    public function swipperOffer(Request $request){
        try{
            $message = 'success';
            $status = 200;
            $data = array();
            $offerId = array();
            $imageList = array();
            $loadQueue = array();
            $user = Auth::user();
            $user_id = $user['id'];
            $customer_offer_type_slug = $request['offerTypeSlug'];
            $customer_category_id = 0;
            $origin = $request['coords'];
            $radius = 1;
            $offers = $this->offerWithinBoundingCircle($origin, $customer_offer_type_slug,  $customer_category_id, $radius);
            if(isset($offers)){
                foreach ($offers as $key => $offer){
                    $offerId[$key] = $offer->id;
                    $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                    $sha1OfferId = sha1($offerId[$key]);
                    $offerImages = $offer->offerImages->first();
                    if(count($offerImages) > 0){
                        $imageList[$key] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offerImages->name;
                        $loadQueue[$key] = 0;
                    }else{
                        $imageList[$key] = '/uploads/no_image.jpg';
                        $loadQueue[$key] = 0;
                    }
                }
            }
            $data = [
                'offerId' => $offerId,
                'imageList' => $imageList,
                'loadQueue' => $loadQueue,
            ];
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Swipper Offers',
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
            $offer_status = 'interested';
            $offer_status_id = OfferStatus::where('slug', $offer_status)->pluck('id')->first();
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $customer_offer_detail = CustomerOfferDetail::where('customer_id', $customer_id)
                ->where('offer_id', $offer_id)
                ->first();
            if (count($customer_offer_detail)>0) {
                $customer_offer_detail->offer_status_id = $offer_status_id;
                $customer_offer_detail->save();
            }else{
                $reach_time_id = ReachTime::where('slug', $reach_time)->pluck('id')->first();
                CustomerOfferDetail::create([
                    'customer_id' => $customer_id,
                    'offer_id' => $offer_id,
                    'offer_status_id' => $offer_status_id,
                    'reach_time_id' => $reach_time_id,
                ]);
            }
            $data = [
                'addedToInterested' => true,
            ];

        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Add Interested Offers',
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
            $sorted_offers = array();
            $user = Auth::user();
            $user_id = $user['id'];
            $sort_by = $request['sortSelected'];
            $currentPage = Input::get('page', 1)-1;
            $customer_offer_type_slug = $request['offerTypeSlug'];
            if ($request['categorySelected'] > 0){
                $customer_category_id = $request['categorySelected'];
            }else{
                $customer_category_id = 0;
            }
            $origin = $request['coords'];
            if ($request['distance'] > 0){
                $radius = $request['distance'];
            }else{
                $radius = 1;
            }
            $offers = $this->offerWithinBoundingCircle($origin, $customer_offer_type_slug,  $customer_category_id, $radius);
            if($sort_by == 'latestFirst'){
                $sort_by_latest = array();
                $sort_by_latest = $offers->sortByDesc('valid_from')->values()->all();
                $near_by_offers = $sort_by_latest;
            }elseif($sort_by == 'expiringSoon'){
                $sort_by_expire =array();
                $sort_by_expire = $offers->sortBy('valid_to')->values()->all();
                $near_by_offers = $sort_by_expire;
            }else{
                foreach ($offers as $key => $offer){
                    $destination['latitude'] = $offer->sellerAddress->latitude;
                    $destination['longitude'] = $offer->sellerAddress->longitude;
                    $distance = $this->getDistanceBetween($origin, $destination);
                    $offer['distance'] = $distance;

                }
                $near_by_offers = $offers->sortBy('distance')->values()->all();
            }
            foreach ($near_by_offers as $key => $customer_offer){
                $seller_user = $customer_offer->sellerAddress->seller->user;
                $sorted_offers[$key]['offerId'] = $customer_offer->id;
                $sorted_offers[$key]['offerName'] = $customer_offer->offerType->name;
                $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                $sha1OfferId = sha1($customer_offer->id);
                if(count($customer_offer->offerImages) > 0){
                    $sorted_offers[$key]['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$customer_offer->offerImages->first()->name;
                }else{
                    $sorted_offers[$key]['offerPic'] = '/uploads/no_image.jpg';
                }
                $sorted_offers[$key]['sellerInfo'] = $seller_user->first_name.' '.$seller_user->last_name;
                $valid_to = $customer_offer->valid_to;
                $sorted_offers[$key]['offerExpiry'] = date('d F, Y',strtotime($valid_to));
            }

            $pagedData = array_slice($sorted_offers, $currentPage * $this->perPage, $this->perPage);

            $data = [
                'records' => $pagedData,
                'pagination' => [
                    'page' => $currentPage + 1,
                    'perPage' => $this->perPage,
                    'pageCount' => count($pagedData),
                    'totalCount' => count($near_by_offers),
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

    public function mapOffers(Request $request)
    {
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $markers = array();
            $near_by_offers = array();
            $sorted_offers = array();
            $mapOffers = array();
            $user = Auth::user();
            $user_id = $user['id'];
            $currentPage = Input::get('page', 1)-1;
            $customer_offer_type_slug = $request['offerTypeSlug'];
            $customer_category_id = $request['categorySelected'];
            $origin = $request['coords'];
            $radius = $request['distance'];

            $offers = $this->offerWithinBoundingCircle($origin, $customer_offer_type_slug,  $customer_category_id, $radius);
            if(count($offers) > 0){
                foreach ($offers as $key => $offer){
                    $seller_user = $offer->sellerAddress->seller->user;
                    $sellerAddress = $offer->sellerAddress;
                    $sorted_offers[$key]['offerId'] = $offer->id;
                    $sorted_offers[$key]['offerName'] = $offer->offerType->name;
                    $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                    $sha1OfferId = sha1($offer->id);
                    if(count($offer->offerImages) > 0){
                        $sorted_offers[$key]['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offer->offerImages->first()->name;
                    }else{
                        $sorted_offers[$key]['offerPic'] = '/uploads/no_image.jpg';
                    }
                    $sorted_offers[$key]['offerAddress'] = $sellerAddress->shop_name.$sellerAddress->address;
                    $sorted_offers[$key]['sellerInfo'] = $seller_user->first_name.' '.$seller_user->last_name;
                    $valid_to = $offer->valid_to;
                    $sorted_offers[$key]['offerExpiry'] = date('d F, Y',strtotime($valid_to));
                    $destination['latitude'] = (double)$sellerAddress->latitude;
                    $destination['longitude'] = (double)$sellerAddress->longitude;
                    $sorted_offers[$key]['coordinate'] = $destination;
                    $distance = $this->getDistanceBetween($origin, $destination);
                    $sorted_offers[$key]['offerDistance'] = $distance;
                }

                $near_by_offers = collect($sorted_offers)->sortBy('offerDistance')->values()->all();
                $pagedData = array_slice($near_by_offers, $currentPage * $this->perPage, $this->perPage);
                foreach ($pagedData as $key => $data){
                    $mapOffers[$key]['offerId'] = $data['offerId'];
                    $mapOffers[$key]['offerName'] = $data['offerName'];
                    $mapOffers[$key]['offerPic'] = $data['offerPic'];
                    $mapOffers[$key]['offerAddress'] = $data['offerAddress'];
                    $mapOffers[$key]['sellerInfo'] = $data['sellerInfo'];
                    $mapOffers[$key]['offerExpiry'] = $data['offerExpiry'];
                    $mapOffers[$key]['offerDistance'] = $data['offerDistance'];
                    $markers[$key]['offerId'] = $data['offerId'];
                    $markers[$key]['coordinate'] = $data['coordinate'];
                    $markers[$key]['key'] = $data['offerId'];
                }
            }
            $data = [
                'records' => $mapOffers,
                'markers' => $markers,
                'pagination' => [
                    'page' => $currentPage + 1 ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($mapOffers),
                    'totalCount' => count($near_by_offers),
                ],
            ];

        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'parameter' => $request->all(),
                'action' => 'mapOffers',
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

    public function ARSellerInfo(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $origin = $request['coords'];
            $radius = $request['distance'];
            $offerTypeSlug = $request['offerTypeSlug'];
            $seller_addresses = array();
            $seller_address_id = array();
            $seller_info = array();
            $offers = $this->offerWithinBoundingCircle($origin, $offerTypeSlug, 0, $radius);
            if(count($offers)>0){
                foreach ($offers as $key => $offer){
                    $seller_addresses[$key] = $offer->sellerAddress;
                    $seller_address_id[$key]['id'] = $offer->sellerAddress->id;
                }
                $seller_addresses = collect($seller_addresses);
                $seller_addresses = $seller_addresses->unique('id')->values()->all();
                foreach ($seller_addresses as $key => $seller_address){
                    $seller_info[$key]['sellerAddressId'] = $seller_address->id;
                    $seller_info[$key]['sellerInfo'] = $seller_address->shop_name.''.$seller_address->address;
                    $seller_info[$key]['latitude'] = (double)$seller_address->latitude;
                    $seller_info[$key]['longitude'] = (double)$seller_address->longitude;
                    $offerCount = collect($seller_address_id)->where('id',$seller_address->id)->count();
                    $seller_info[$key]['offerCount'] = $offerCount;
                    $seller_info[$key]['floorId'] = $seller_address->floor_id;
                }

            }else{
                $message = "There No offer in your nearby";
            }
            $data = [
                'records' => $seller_info
            ];
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'parameter' => $request->all(),
                'action' => 'AR Seller Info',
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

    public function AROffers(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $nearByOffers = array();
            $currentPage = Input::get('page', 1)-1;
            $seller_address_id = $request['sellerAddressId'];
            $offerTypeSlug = $request['offerTypeSlug'];

            if($offerTypeSlug == 'all'){
                $offers = Offer::where('seller_address_id', $seller_address_id)->get();

            }else{
                $offer_type_id = OfferType::where('slug', $offerTypeSlug)->pluck('id')->first();
                $offers = Offer::where('seller_address_id', $seller_address_id)
                    ->where('offer_type_id', $offer_type_id)->get();
            }
            if(count($offers)>0){
                foreach ($offers as $key => $offer){
                    $imageUploadPath = env('OFFER_IMAGE_UPLOAD');
                    $sha1OfferId = sha1($offer['id']);
                    $nearByOffers[$key]['offerId'] = $offer->id;
                    $nearByOffers[$key]['offerName'] = $offer->offerType->name;
                    $offerImages = $offer->offerImages;
                    if(count($offerImages) > 0){
                        $nearByOffers[$key]['offerPic'] = $imageUploadPath.$sha1OfferId.DIRECTORY_SEPARATOR.$offerImages->first()->name;

                    }else{
                        $nearByOffers[$key]['offerPic'] = '/uploads/no_image.jpg';
                    }
                    $seller = $offer->sellerAddress->seller;
                    $nearByOffers[$key]['sellerInfo'] = $seller->user->first_name.' '.$seller->user->last_name;
                    $valid_to = $offer->valid_to;
                    $nearByOffers[$key]['offerExpiry']= date('d F, Y',strtotime($valid_to));
                }

            }else{
                $message = "There No offer in your nearby";
            }
            $pagedData = array_slice($nearByOffers, $currentPage * $this->perPage, $this->perPage);

            $data = [
                'records' => $pagedData,
                'pagination' => [
                    'page' => $currentPage + 1,
                    'perPage' => $this->perPage,
                    'pageCount' => count($pagedData),
                    'totalCount' => count($offers),
                ],
            ];
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'parameter' => $request->all(),
                'action' => 'AR Offer Detail',
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

    public function offerWithinBoundingCircle($origin, $customer_offer_type_slug,  $customer_category_id, $radius){
        try{
            $near_by_offers = array();
            $latitude = $origin['latitude'];
            $longitude = $origin['longitude'];
            $earth_radius = 6371;
            $maxLat = $latitude + rad2deg($radius/$earth_radius);
            $minLat = $latitude - rad2deg($radius/$earth_radius);
            $maxLon = $longitude + rad2deg(asin($radius/$earth_radius) / cos(deg2rad($latitude)));
            $minLon = $longitude - rad2deg(asin($radius/$earth_radius) / cos(deg2rad($latitude)));

            $near_by_seller_addresses = SellerAddress::select('id','zipcode', 'latitude', 'longitude')
                ->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLon, $maxLon])
                ->pluck('id')->all();

            $offers = Offer::whereIn('seller_address_id', $near_by_seller_addresses)
                            ->get();

            if($customer_category_id > 0){
                $category_id = Category::where('id',$customer_category_id)
                    ->pluck('category_id')->first();
                if(isset($category_id)){

                    $sort_by_category = $offers->whereIn('category_id', $customer_category_id);

                }else{
                    $category_id = Category::where('category_id',$customer_category_id)
                        ->pluck('id')->all();
                    if(sizeof($category_id)>0){
                        $sort_by_category = $offers->whereIn('category_id', $category_id);
                    }else{
                        $sort_by_category = $offers->whereIn('category_id', $customer_category_id);
                    }
                }

                $offer_type_id = OfferType::where('slug', $customer_offer_type_slug )
                    ->pluck('id')->first();
                if (isset($offer_type_id)){
                    $sort_by_offers_type = $sort_by_category->where('offer_type_id', $offer_type_id);

                }else{
                    $sort_by_offers_type = $sort_by_category;
                }
                $near_by_offers = $sort_by_offers_type;
            }else{

                $near_by_offers = $offers;
            }

            return $near_by_offers;

        }catch (\Exception $e){
            $data =[
                'parameter' => [
                    'origin' => $origin,
                    'radius' => $radius
                ],
                'action' => 'sorting Offer to customer bound',
                'errorMessage' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
    }

    public function getDistanceBetween($origin, $destination ,$unit = 'km', $decimals = 1){
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
            return number_format($distance, $decimals);
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

    public function getGrabCode(Request $request){
        try{
            $status = 200;
            $data = array();
            $offerId = $request['offerId'];

            $customerOfferDetail = CustomerOfferDetail::where('offer_id', $offerId)->first();
            //if(count($customerOfferDetail->offer_code)>0){
                if($customerOfferDetail->offer_code != null){
                    $grab_code = str_random(5);

                $customerOfferDetail->update([
                    'offer_code' => $grab_code
                ]);
                $message = 'Grab Code Generated Successfully';
            }else{
                $grab_code = '';
                $message = 'please enter a valid offerId';
            }

            $data = [
                'grabCode' => $grab_code,
            ];
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'parameter' => $request->all(),
                'action' => 'Get Grab Code',
                'errorMessage' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
    }


}