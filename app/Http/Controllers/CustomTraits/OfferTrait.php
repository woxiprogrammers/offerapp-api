<?php
    /**
     * Created by PhpStorm.
     * User: Arvind
     * Date: 20/4/18
     * Time: 11:41 AM
     */


namespace App\Http\Controllers\CustomTraits;

use App\Floor;
use App\Offer;
use App\OfferImage;
use App\OfferStatus;
use App\OfferType;
use App\ReachTime;
use App\SellerAddress;
use App\Category;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait OfferTrait{

    public function getOfferType(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $offerTypes = OfferType::select('id', 'name','slug')->get();
            $data['offerTypes'] = $offerTypes;
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get OfferType',
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

    public function getFloor(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $floors = Floor::select('id', 'name','slug')->get();
            $data['floors'] = $floors;
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get Floor',
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

    public function getReachInTime(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $reach_in_time = ReachTime::select('id', 'name','slug')->get();
            $data['reachInTime'] = $reach_in_time;
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get Reach In Time',
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

    public function getCategory(Request $request){
        try{
            $message = "Success";
            $status = 200;
            $data = array();
            $categories = Category::whereNull('category_id')->select('id', 'name','slug')->get();
            foreach ($categories as $key => $category){
                if (isset($category->id)){
                    $sub_category = Category::whereNotNull('category_id')
                        ->where('category_id', $category->id)
                        ->select('id','name','slug')
                        ->get();

                        $categories[$key]['subCategory'] = $sub_category;
                }else{
                    $categories[$key] = $category;
                }

            }
            $data['categories'] = $categories;
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Get Offer Categories-subcategories',
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
}