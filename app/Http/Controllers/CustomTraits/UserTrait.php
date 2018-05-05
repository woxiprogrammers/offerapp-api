<?php
/**
 * Created by PhpStorm.
 * User: arvind
 * Date: 3/5/18
 * Time: 8:14 AM
 */

namespace App\Http\Controllers\CustomTraits;

use App\Customer;
use App\Seller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;


trait UserTrait{
    public function editProfile(Request $request){
        try{
            $message = 'Success';
            $status = 200;
            $userData = $request->except('token','peProfilePicBase64');
            $data = array();
            $user = Auth::user();
            $user->update([
                'first_name' => $userData['FirstName'],
                'last_name' => $userData['LastName'],
                'email' => $userData['Email'],
            ]);

            if($request->has('ProfilePicBase64')){
                $image = base64_decode($request['ProfilePicBase64']);
                $filename = $user->profile_picture;
                if($user->role->slug == 'seller'){
                    $sha1SellerId = Seller::where('user_id', $user['id'])->pluck('id')->first();
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('SELLER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1SellerId.DIRECTORY_SEPARATOR;

                }else{
                    $sha1CustomerId = Customer::where('user_id', $user['id'])->pluck('id')->first();
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('CUSTOMER_PROFILE_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1CustomerId.DIRECTORY_SEPARATOR;

                }
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
            $data['userData']['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : $path;
            $message = "Profile Updated in successfully!!";
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

            $user = Auth::user();
            if($request['credentialSlug'] == 'mobile_no'){

                $newMobileNo = $request['mobileNo'];
                $user->update([
                    'mobile_no' => $newMobileNo
                ]);
                $message = 'Mobile No Updated Successfully';
                JWTAuth::invalidate($request['token']);
            }else{
                $newPassword = $request['password'];
                $user->update([
                    'password' => Hash::make($newPassword)
                ]);
                $message = 'Password Updated Successfully';
            }
            $data = [
                'userData' => $user
            ];
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

}