<?php
    /**
     * Created by PhpStorm.
     * User: sonali
     * Date: 22/3/18
     * Time: 4:28 PM
     */


namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginController extends BaseController
{

    public function __construct(){
        $this->middleware('jwt.auth',['except' => ['login']]);
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
                $userData['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
                $userData['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.png' : env('OFFER_IMAGE_UPLOAD').$user['profile_picture'];
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

}