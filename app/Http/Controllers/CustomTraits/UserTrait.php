<?php
/**
 * Created by PhpStorm.
 * User: arvind
 * Date: 3/5/18
 * Time: 8:14 AM
 */

namespace App\Http\Controllers\CustomTraits;

use App\Customer;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Otp;
use App\Seller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\String_;
use Tymon\JWTAuth\Facades\JWTAuth;


trait UserTrait{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function editProfilePicture(Request $request)
    {
        try{
            $message = '';
            $status = 200;
            $user = Auth::user();
            switch ($request['image_for']){
                case 'customer-profile-edit' :
                    $sha1CustomerId = sha1(Customer::where('user_id', $user['id'])->pluck('id')->first());
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('CUSTOMER_PROFILE_IMAGE_UPLOAD').$sha1CustomerId.DIRECTORY_SEPARATOR;
                    break;

                case 'seller-profile-edit' :
                    $sha1SellerId = sha1(Seller::where('user_id', $user['id'])->pluck('id')->first());
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('SELLER_PROFILE_IMAGE_UPLOAD').$sha1SellerId.DIRECTORY_SEPARATOR;
                    break;
                default :
                    $imageUploadPath = '';
            }
            if (!file_exists($imageUploadPath)) {
                File::makeDirectory($imageUploadPath, $mode = 0777, true, true);
            }
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = mt_rand(1,10000000000).sha1(time()).".{$extension}";
            $request->file('image')->move($imageUploadPath,$filename);
            $message = "Success";
            $status = 200;
            $data = [
                'profilePicName' => $filename
            ];
        }catch (\Exception $e){
            $data = [
                'action' => 'edit Profile Picture',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

    public function editProfile(Request $request){
        try{

            $userData = $request->except('token');
            $data = array();
            $user = Auth::user();
            if($user->role->slug == 'seller'){
                $sha1SellerId = sha1(Seller::where('user_id', $user['id'])->pluck('id')->first());
                $imageUploadPath = env('SELLER_PROFILE_IMAGE_UPLOAD').$sha1SellerId.DIRECTORY_SEPARATOR;

            }else{
                $sha1CustomerId = sha1(Customer::where('user_id', $user['id'])->pluck('id')->first());
                $imageUploadPath = env('CUSTOMER_PROFILE_IMAGE_UPLOAD').$sha1CustomerId.DIRECTORY_SEPARATOR;

            }
            $user->update([
                'first_name' => $userData['firstName'],
                'last_name' => $userData['lastName'],
                'email' => $userData['email'],
                'profile_picture' => $userData['profilePicName'],
            ]);
            $imageUploadPath .= $request['profilePicName'];
            $data['userData']['firstName'] = $user['first_name'];
            $data['userData']['lastName'] = $user['last_name'];
            $data['userData']['email'] = $user['email'];
            $data['userData']['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
            $data['userData']['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : $imageUploadPath;
            $message = "Profile Updated  successfully!!";
            $status = 200;

        }catch (\Exception $e){
            $message = 'Fail';
            $status = 500;
            $data = [
                'action' => 'edit Profile',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

    public function changeCredential(Request $request){
        try{
            $user = Auth::user();
            if($request['credentialSlug'] == 'mobile_no'){
                $newMobileNo = $request['mobile_no'];
                $otp = Otp::where('mobile_no',$newMobileNo)->pluck('otp')->last();
                if($otp == $request['otp']) {
                    $user->update([
                        'mobile_no' => $newMobileNo
                    ]);
                    $message = 'Mobile No Updated Successfully';
                    $status = 200;
                        JWTAuth::invalidate($request['token']);
                 }else{
                    $message = 'Invalid Otp';
                    $status = 401;
                }
            }else{
                $newPassword = $request['password'];
                $user->update([
                    'password' => Hash::make($newPassword)
                ]);
                $message = 'Password Updated Successfully';
                $status = 200;

            }
            $data = [
                'userData' => $user
            ];
        }catch (\Exception $e){
            $status = 500;
            $message = 'fail';
            $data = [
                'action' => 'Change Credential',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
    }

    public function getUserData(Request $request){
        try{
            $user = Auth::user();
            $data = array();
            if($user->role->slug == 'seller'){
                $sha1SellerId = sha1(Seller::where('user_id', $user['id'])->pluck('id')->first());
                $imagePath = env('SELLER_PROFILE_IMAGE_UPLOAD').$sha1SellerId.DIRECTORY_SEPARATOR.$user['profile_picture'];

            }else{
                $sha1CustomerId = sha1(Customer::where('user_id', $user['id'])->pluck('id')->first());
                $imagePath = env('CUSTOMER_PROFILE_IMAGE_UPLOAD').$sha1CustomerId.DIRECTORY_SEPARATOR.$user['profile_picture'];
            }
            $data['userData']['firstName'] = $user['first_name'];
            $data['userData']['lastName'] = $user['last_name'];
            $data['userData']['email'] = $user['email'];
            $data['userData']['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
            $data['userData']['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : $imagePath;
            $message = "Profile Data Fetched successfully!!";
            $status = 200;

        }catch (\Exception $e){
            $data = [
                'action' => 'Get User Data',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'data' => $data,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

}