<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20/4/18
 * Time: 4:09 PM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;


class ImageController extends BaseController{

    public function __construct()
    {
        $this->middleware('jwt.auth');
        if (!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function image(Request $request){
        try{
            $user = Auth::user();
            $sha1UserId = sha1($user['id']);
            switch ($request['image_for']){
                case 'offer-create' :
                    $tempUploadPath = env('WEB_PUBLIC_PATH').env('OFFER_TEMP_IMAGE_UPLOAD');
                    break;

                case 'seller-address' :
                    $tempUploadPath = env('WEB_PUBLIC_PATH').env('SELLER_ADDRESS_TEMP_IMAGE_UPLOAD');
                    break;
                default :
                    $tempUploadPath = '';
            }
            $tempImageUploadPath = $tempUploadPath.DIRECTORY_SEPARATOR.$sha1UserId;
            if (!file_exists($tempImageUploadPath)) {
                File::makeDirectory($tempImageUploadPath, $mode = 0777, true, true);
            }
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = mt_rand(1,10000000000).sha1(time()).".{$extension}";
            $request->file('image')->move($tempImageUploadPath,$filename);
            $message = "Success";
            $status = 200;
        }catch (\Exception $e){
            $data = [
                'action' => 'Save temporary Images',
                'exception' => $e->getMessage(),
                'request' => $request->all()
            ];
            $message = $e->getMessage();
            $status = 500;
            $filename = null;
            Log::critical(json_encode($data));
        }
        $response = [
            "message" => $message,
            "filename" => $filename
        ];
        return response()->json($response,$status);
    }

}