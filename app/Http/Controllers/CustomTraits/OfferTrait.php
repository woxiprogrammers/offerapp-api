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
            $offerTypes = OfferType::select('id', 'name','slug')->get();
            $data = [
                'offerTypes' => $offerTypes
            ];
        }catch (\Exception $e){
            $data = [
                'action' => 'Get OfferType',
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
        }
        return response()->json($data);

    }

    public function getFloor(Request $request){
        try{
            $floors = Floor::select('id', 'name','slug')->get();
            $data = [
                'floors' => $floors
            ];

        }catch (\Exception $e){
            $data = [
                'action' => 'Get Floor',
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));

        }
        return response()->json($data);
    }

    public function getReachInTime(Request $request){
        try{
            $reach_in_time = ReachTime::select('id', 'name','slug')->get();
            $data = [
                'ReachInTime' => $reach_in_time
            ];
        }catch (\Exception $e){
            $data = [
                'action' => 'Get Reach In Time',
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
        }
        return response()->json($data);
    }

    public function getCategory(Request $request){
        try{
            $categories = Category::where('category_id',NULL)
                                ->select('id', 'name','slug')->get();
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
            $data =[
                'Categories' => $categories
            ];
        }catch (\Exception $e){
            $data = [
                'action' => 'Get Offer Categories',
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
        }
        return response()->json($data);
    }
    

}