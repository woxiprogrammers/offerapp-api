<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 22/3/18
 * Time: 4:28 PM
 */


namespace App\Http\Controllers\Auth;

use App\Customer;
use App\Seller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends BaseController
{

    public function __construct(){
        $this->middleware('jwt.auth',['except' => ['login', 'forgotPassword']]);
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function login(Request $request){
        try{
            $credentials = $request->only('mobile_no','password');
            $userData =  array();

            if($token = JWTAuth::attempt($credentials)){
                $user = Auth::user();
                $userData['firstName'] = $user['first_name'];
                $userData['lastName'] = $user['last_name'];
                $userData['email'] = $user['email'];
                if($user->role->slug == 'seller'){
                    $sha1SellerId = sha1(Seller::where('user_id', $user['id'])->pluck('id')->first());
                    $imageUploadPath = env('SELLER_PROFILE_IMAGE_UPLOAD').$sha1SellerId.DIRECTORY_SEPARATOR;
                }else{
                    $sha1CustomerId = sha1(Customer::where('user_id', $user['id'])->pluck('id')->first());
                    $imageUploadPath = env('CUSTOMER_PROFILE_IMAGE_UPLOAD').$sha1CustomerId.DIRECTORY_SEPARATOR;

                }
                $imageUploadPath .= $user['profile_picture'];
                $userData['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
                $userData['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : $imageUploadPath;
                $message = "Logged in successfully!!";
                $status = 200;
            }else{
                $message = "Invalid credentials";
                $status = 401;
            }

        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $userData =  array();
            $token = '';
            $data = [
                'action' => 'Login',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
            'token' => $token,
            'userData' => $userData
        ];
        return response()->json($response,$status);
    }

    public function logout(Request $request){
        try{
            $token = $request['token'];
            if(JWTAuth::invalidate($token)){
                $message = "Logout Successfully";
                $status = 200;
            }else{
                $message = "Sorry Can't Logout";
                $status = 500;
            }
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'logout',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
            'token' => $token
        ];
        return response()->json($response,$status);
    }

    public function forgotPassword(Request $request){
        try{
            $user = User::where('mobile_no', $request['mobileNo'])->first();
            if(count($user) > 0){
                $user->update([
                    'password' => Hash::make($request['password'])
                ]);
                $message = "Password Changed Successfully";
                $status = 200;
            }else{
                $message = "Please Enter a Valid Mobile No.!!";
                $status = 401;
            }
        }catch (\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'logout',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
        ];
        return response()->json($response,$status);
    }

}