<?php
/**
 * Created by PhpStorm.
 * User: harsha
 * Date: 25/3/18
 * Time: 10:40 PM
 */

namespace App\Http\Controllers\Seller;

use App\Category;
use App\OfferImage;
use App\OfferStatus;
use App\OfferType;
use App\Seller;
use App\Offer;
use App\SellerAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;


class OfferController extends BaseController
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        if (!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getOfferListing(Request $request)
    {
        try {
            $user = Auth::user();
            $seller = Seller::where('user_id', $user['id'])->first();
            $sellerAddresses = $seller->sellerAddress;
            $iterator = 0;
            $offerList = array();
            foreach ($sellerAddresses as $key => $sellerAddress) {
                if ($request['status_slug'] == 'all') {
                    $offers = $sellerAddress->offer;
                } else {
                    $offerStatusId = OfferStatus::where('slug', $request['status_slug'])->pluck('id')->first();
                    $offers = $sellerAddress->offer->where('offer_status_id', $offerStatusId);
                }
                foreach ($offers as $key2 => $offer) {
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

        } catch (\Exception $e) {
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
        return response()->json($response, $status);
    }

    public function getOfferDetail(Request $request)
    {
        try {
            $offerId = $request['offer_id'];
            $offer = Offer::where('id', $offerId)->first();
            $offerList = array();
            $offerList['offer_id'] = $offer['id'];
            $sellerAddress = $offer->sellerAddress;
            $offerList['seller_address_id'] = $sellerAddress->id;
            $offerList['floor_no'] = $sellerAddress->floor->no;
            $offerList['seller_address'] = $sellerAddress->shop_name . ' ' . $sellerAddress->city;
            $offerList['full_seller_address'] = $sellerAddress->floor->no . ' ' . $sellerAddress->shop_name . ' ' . $sellerAddress->address . ' ' . $sellerAddress->city . ' ' . $sellerAddress->state . ' ' . $sellerAddress->zipcode;
            // $offerList['offer_images'] = $offer->offerImages->name;
            $offerList['offer_type_name'] = $offer->offerType->name;
            $offerList['offer_status_name'] = $offer->offerStatus->name;
            $offerList['offer_description'] = ($offer->description == null) ? '' : $offer->description;
            $valid_from = $offer->valid_from;
            $valid_to = $offer->valid_to;
            $offerList['start_date'] = date('d F, Y', strtotime($valid_from));
            $offerList['end_date'] = date('d F, Y', strtotime($valid_to));
            $data['offer_detail'] = $offerList;
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Seller Offer Detail',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $status);
    }

    public function getOfferType(Request $request){
        try {
            $offerTypes = OfferType::all();
            $iterator = 0;
            $offerTypeList = array();
            foreach ($offerTypes as $key => $offerType) {
                $offerTypeList[$iterator]['offer_type_id'] = $offerType['id'];
                $offerTypeList[$iterator]['offer_type_name'] = $offerType['name'];
                $offerTypeList[$iterator]['offer_type_slug'] = $offerType['slug'];
                $iterator++;
            }
            $data['select_offer_type'] = $offerTypeList;
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Offer Type Listing',
                'exception' => $e->getMessage(),
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $status);
    }

    public function createOffer(Request $request){
        try {
            $user = Auth::user();
            $input = $request->all();
            $seller_id = Seller::where('user_id', $user['id'])->pluck('id')->first();
            $seller_address_id = SellerAddress::where('seller_id', $seller_id)->pluck('id')->first();
            $offer_status_id = OfferStatus::where('slug', 'pending')->pluck('id')->first();

            $offer = Offer::create([
                'category_id' => $input['category_id'],
                'offer_type_id' => $input['offer_type_id'],
                'seller_address_id' => $seller_address_id,
                'offer_status_id' => $offer_status_id,
                'description' => $input['offer_description'],
                'valid_from' => $input['start_date'],
                'valid_to' => $input['end_date']

            ]);
            if ($request->has('images')) {
                $sha1UserId = sha1($user['id']);
                $sha1OfferId = sha1($offer['id']);
                foreach ($input['images'] as $key1 => $imageName) {
                    $tempUploadFile = env('WEB_PUBLIC_PATH') . env('OFFER_TEMP_IMAGE_UPLOAD'). DIRECTORY_SEPARATOR . $sha1UserId . DIRECTORY_SEPARATOR . $imageName;
                    if (File::exists($tempUploadFile)) {
                        $imageUploadNewPath = env('WEB_PUBLIC_PATH') . env('OFFER_IMAGE_UPLOAD') . $sha1OfferId;
                        if (!file_exists($imageUploadNewPath)) {
                            File::makeDirectory($imageUploadNewPath, $mode = 0777, true, true);
                        }
                        $imageUploadNewPath .= DIRECTORY_SEPARATOR . $imageName;
                        File::move($tempUploadFile, $imageUploadNewPath);
                        OfferImage::create(['name' => $imageName, 'offer_id' => $offer->id]);
                    }
                }
            }
            $message = "Success";
            $status = 200;
        }catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Create Seller Offer',
                'exception' => $e->getMessage(),
                'params' => $request->all()

            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }



}
