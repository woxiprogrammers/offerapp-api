<?php
/**
 * Created by PhpStorm.
 * User: harsha
 * Date: 25/3/18
 * Time: 10:40 PM
 */

namespace App\Http\Controllers\Seller;

use App\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;


class CategoryController extends BaseController
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['getSubCategory', 'getMainCategory']]);
        if (!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getMainCategory()
    {
        try {
            $main_category = Category::all()->where('category_id',null);
            $iterator = 0;
            $categoryList = array();
            foreach ($main_category as $key => $category) {
                $categoryList[$iterator]['category_id'] = $category['id'];
                $categoryList[$iterator]['category_name'] = $category['name'];
                $categoryList[$iterator]['category_slug'] = $category['slug'];
                $iterator++;
            }
            $data['select_category_type'] = $categoryList;
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Category Listing',
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

    public function getSubCategory(Request $request){
        try{
            $main_category_id = $request['category_id'];
            $sub_category = Category::all()->where('category_id', $main_category_id);
            $iterator = 0;
            $categoryList = array();
            foreach ($sub_category as $key => $category) {
                $categoryList[$iterator]['category_id'] = $category['id'];
                $categoryList[$iterator]['category_name'] = $category['name'];
                $categoryList[$iterator]['category_slug'] = $category['slug'];
                $iterator++;
            }
            $data['select_category_type'] = $categoryList;
            $message = 'Success';
            $status = 200;
        }catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Category Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
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

}
