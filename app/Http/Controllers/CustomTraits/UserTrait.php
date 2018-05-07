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
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;


trait UserTrait{
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }
    public function editProfile(Request $request){
        try{
            $message = 'Success';
            $status = 200;
            $userData = $request->except('token');

            $data = array();
            $user = Auth::user();
            $user->update([
                'first_name' => $userData['firstName'],
                'last_name' => $userData['lastName'],
                'email' => $userData['email'],
            ]);
            if($user->role->slug == 'seller'){
                $sha1SellerId = Seller::where('user_id', $user['id'])->pluck('id')->first();
                $imageUploadPath = env('WEB_PUBLIC_PATH').env('SELLER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1SellerId.DIRECTORY_SEPARATOR;

            }else{
                $sha1CustomerId = Customer::where('user_id', $user['id'])->pluck('id')->first();
                $imageUploadPath = env('WEB_PUBLIC_PATH').env('CUSTOMER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1CustomerId.DIRECTORY_SEPARATOR;
            }
            if($request->has($request['profilePicBase64'])){
                $image = base64_decode($request['profilePicBase64']);
                $filename = $user->profile_picture;

                //DELETES EXISTING
                if (file_exists($imageUploadPath.$filename)){
                    unlink($imageUploadPath.$filename);
                }

                //CREATING NEW
                File::makeDirectory($imageUploadPath, $mode = 0777, true, true);
                $filename = mt_rand(1,10000000000).sha1(time()).".jpg";
                file_put_contents($imageUploadPath.$filename, $image);
                $path = $imageUploadPath.$filename;
                $user->update([
                    'profile_picture' => $filename
                ]);
            }
            $data['userData']['firstName'] = $user['first_name'];
            $data['userData']['lastName'] = $user['last_name'];
            $data['userData']['email'] = $user['email'];
            $data['userData']['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
            $data['userData']['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : $imageUploadPath.$user['profile_picture'];
            $message = "Profile is Updated successfully!!";
            $status = 200;

        }catch (\Exception $e){
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
            $status = 200;
            $message = '';
            $data = array();
            $user = Auth::user();

            if($request['credentialSlug'] == 'mobile_no'){
                $newMobileNo = $request['mobile_no'];
                $userotp = $request['otp'];
                $otp = Otp::where('mobile_no',$newMobileNo)->orderBy('id','desc')->first();
                if($otp['otp'] == $userotp) {
                    $user->update([
                        'mobile_no' => $newMobileNo
                    ]);
                    $data = [
                        'userData' => $user
                    ];
                    $message = 'Mobile No Updated Successfully';
                    $status = 200;
                    $otp->delete();
                    JWTAuth::invalidate($request['token']);

                }else{
                    $data = [
                        'userData' => ''
                    ];
                    $message = "Invalid Otp...Please Enter Correct Otp";
                    $status = 412;
                }

            }else{
                $newPassword = $request['password'];
                $user->update([
                    'password' => Hash::make($newPassword)
                ]);
                $data = [
                    'userData' => $user
                ];
                $message = 'Password Updated Successfully';
            }

        }catch (\Exception $e){
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
                $sha1SellerId = Seller::where('user_id', $user['id'])->pluck('id')->first();
                $imagePath = env('WEB_PUBLIC_PATH').env('SELLER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1SellerId.DIRECTORY_SEPARATOR.$user['profile_picture'];

            }else{
                $sha1CustomerId = Customer::where('user_id', $user['id'])->pluck('id')->first();
                $imagePath = env('WEB_PUBLIC_PATH').env('CUSTOMER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1CustomerId.DIRECTORY_SEPARATOR.$user['profile_picture'];
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