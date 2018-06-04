<?php

namespace App\Http\Controllers;

use App\Category;
use App\Offer;
use App\OfferImage;
use App\OfferStatus;
use App\Role;
use App\Seller;
use App\SellerAddress;
use App\User;
use Carbon\Carbon;
use Cornford\Googlmapper\Facades\MapperFacade as Mapper;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Ixudra\Curl\Facades\Curl;


class ExampleController extends Controller
{

    public function __construct()
    {
        //
    }


    public function autoGenerateOffer(Request $request){
        try{
            $origin = $request['origin'];
            $radius = $request['radius'];
            $count = $request['count'];
            $latitude = $origin['latitude'];
            $longitude = $origin['longitude'];
            $earth_radius = 6371;
            $maxLat = $latitude + rad2deg($radius/$earth_radius);
            $minLat = $latitude - rad2deg($radius/$earth_radius);
            $maxLon = $longitude + rad2deg(asin($radius/$earth_radius) / cos(deg2rad($latitude)));
            $minLon = $longitude - rad2deg(asin($radius/$earth_radius) / cos(deg2rad($latitude)));
            $firstName = ['Ravi', 'Rajesh', 'Mukesk', 'Deepali', 'Arun', 'Akshay'];
            $lastName = ['Sharma', 'Joshi', 'Rathod', 'Choudhary', 'Chavan', 'Gupta'];
            $shop_name = ['Levis', 'Bata', 'Lenovo', 'Fittnes Club', 'Champion', 'McDonald\'s', 'Hotel P. K'];
            $category_slug = ['clothing', 'footwear', 'laptop-tablet', 'gym', 'sports', 'snacks-center', 'night-life'];
            $offer_status_id = OfferStatus::where('type','seller')->pluck('id');
            $offer_image_name = ['offer1.jpg','offer2.jpg','offer3.jpg'];
            $address = '';
            $city = '';
            $state = '';

            for ($iterator = 0; $iterator < $count; $iterator++){

                $destination = [
                    'latitude' => rand($minLat*100000000,$maxLat*100000000)/100000000,
                    'longitude' => rand($minLon*100000000,$maxLon*100000000)/100000000
                ];

                $latlng = implode(",", [$destination['latitude'], $destination['longitude']]);
                $apiKey = urlencode(env('GOOGLE_API_KEY'));
                $response = Curl::to('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$latlng.'&key='.$apiKey)
                    ->post();

                $result = json_decode($response);
                $near_by_addresses = $result->results[0]->formatted_address;

                $role_id = Role::where('slug', 'seller')->pluck('id')->first();
                $first_name = $firstName[rand(0,sizeof($firstName)-1)];
                $last_name = $lastName[rand(0,sizeof($firstName)-1)];

                $user = User::create([
                    'role_id' => $role_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'mobile_no' => rand(1111111111,9999999999),
                    'email' => strtolower($first_name).strtolower($last_name).'@gmail.com',
                    'profile_picture' => '',
                    'password' => Hash::make('123456'),
                ]);
                $users[$iterator] = $user;
                $seller = Seller::create([
                    'user_id' => $user->id,
                ]);
                $sellers[$iterator] = $seller;


                $location = Mapper::location($near_by_addresses);
                $google_addresses = explode(',', $location->getAddress());
                $size_of_google_addresses = sizeof($google_addresses);

                foreach ($google_addresses as $key => $google_address) {
                    if ($key < $size_of_google_addresses - 4 ) {
                        if($address == ''){
                            $address = $google_address;
                        }else{
                            $address = $address . ', ' . $google_address;

                        }
                    } elseif ($key == $size_of_google_addresses - 4) {
                        $city = $google_address;
                    } elseif ($key == $size_of_google_addresses - 3) {
                        $state = $google_address;
                    }
                }

                $category_type_index = rand(0, sizeof($category_slug)-1);

                $seller_address = SellerAddress::create([
                    'seller_id' => $seller->id,
                    'shop_name' => $shop_name[$category_type_index],
                    'landline' => rand(11111111,99999999),
                    'address' => $address,
                    'floor_id'=> 2,
                    'zipcode' => $location->getPostalCode(),
                    'city' => $city,
                    'state' => $state,
                    'longitude' => $location->getLongitude(),
                    'latitude' => $location->getLatitude(),
                    'is_active'=> true,

                ]);
                $seller_addresses[$iterator] = $seller_address;

                for ($offerType = 1; $offerType <= 4; $offerType++){
                    $category_id = Category::where('slug', $category_slug[$category_type_index])->pluck('id')->first();
                    $start_date = Carbon::now();
                    $end_date = Carbon::now()->modify('+7 day');

                    $offer = Offer::create([
                        'category_id' => $category_id,
                        'offer_type_id' => $offerType,
                        'seller_address_id' => $seller_address->id,
                        'offer_status_id' => rand(1, sizeof($offer_status_id)),
                        'description' => 'Enjoy shopping at '.$seller_address->shop_name,
                        'valid_from' => date('Y-m-d H:i',strtotime($start_date)),
                        'valid_to' => date('Y-m-d H:i',strtotime($end_date))
                    ]);

                    $offers[$iterator]['offers'][$offerType] = $offer;

                    $tempOfferImageName = $offer_image_name[rand(0, sizeof($offer_image_name)-1)];
                    $sha1OfferId = sha1($offer->id);
                    $temp_offer_image = env('WEB_PUBLIC_PATH').env('TEMP_OFFER_IMAGE_UPLOAD').$tempOfferImageName;

                    $imageUploadNewPath = env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$sha1OfferId;
                    if (!file_exists($imageUploadNewPath)) {
                        File::makeDirectory($imageUploadNewPath, $mode = 0777, true, true);
                    }

                    /*Spliting the tempOfferImageName to get Extension*/
                    $splitImageName = explode('.',$tempOfferImageName);
                    $extension = $splitImageName[1];
                    $filename = mt_rand(1,10000000000).sha1(time()).".{$extension}";
                    $imageUploadNewPath .= DIRECTORY_SEPARATOR . $filename;
                    /*File::copy($temp_offer_image, $imageUploadNewPath);*/
                    $offerImage = OfferImage::create(['name' => $filename, 'offer_id' => $offer->id]);
                    $offers[$iterator]['offerImages'][$offerType] = $offerImage;
                }


            }
            $data = [
                'user' => $users,
                'seller' => $sellers,
                'seller_address' => $seller_addresses,
                'offer' => $offers
            ];

        }catch (\Exception $e){
            $status = 500;
            $message = 'fail';
            $data = [
                'parameter' => $request->all(),
                'action' => 'generate offer Script',
                'exception' => $e->getMessage(),
            ];
            Log::critical(json_encode($data));
        }
        return response()->json($data);
    }

}
