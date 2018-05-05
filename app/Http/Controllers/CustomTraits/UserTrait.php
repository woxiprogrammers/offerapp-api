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
                'first_name' => $userData['peFirstName'],
                'last_name' => $userData['peLastName'],
                'email' => $userData['peEmail'],
            ]);

            if($request->has('peProfilePicBase64')){
                $image = base64_decode($request['peProfilePicBase64']);
                $sha1UserId = sha1($user['id']);
                $filename = $user->profile_picture;
                if($user->role->slug == 'seller'){
                    $sha1SellerId = Seller::where('user_id', $user['id'])->pluck('id')->first();
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('SELLER_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1SellerId.DIRECTORY_SEPARATOR;

                }else{
                    $sha1CustomerId = Customer::where('user_id', $user['id'])->pluck('id')->first();
                    $imageUploadPath = env('WEB_PUBLIC_PATH').env('CUSTOMER_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1CustomerId.DIRECTORY_SEPARATOR;

                }
                //DELETES EXISTING
                if (file_exists($imageUploadPath.$filename)){
                    unlink($imageUploadPath.$filename);
                }

                //CREATING NEW
                File::makeDirectory($imageUploadPath, $mode = 0777, true, true);
                $filename = mt_rand(1,10000000000).sha1(time()).".jpg";
                file_put_contents($imageUploadPath.$filename, $image);
                $path = env('WEB_PUBLIC_PATH').env('SELLER_IMAGE_UPLOAD').DIRECTORY_SEPARATOR.$sha1UserId.DIRECTORY_SEPARATOR.$filename;
                $user->update([
                    'profile_picture' => $filename
                ]);
            }
            $data = [
                'path' => $path
            ];

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
                $message = 'Mobile No Updated Successfully';
                $newMobileNo = $request['mobileNo'];
                $user->update([
                    'mobile_no' => $newMobileNo
                ]);
                JWTAuth::invalidate($request['token']);
            }else{
                $message = 'Password Updated Successfully';
                $newPassword = $request['password'];
                $user->update([
                    'password' => Hash::make($newPassword)
                ]);
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