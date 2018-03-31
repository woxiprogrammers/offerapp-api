<?php
    /**
     * Created by PhpStorm.
     * User: harsha
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

    public function __construct()
    {
        $this->middleware('jwt.auth',['except' => ['login']]);
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function login(Request $request){
        try{
            $credentials = $request->only('mobile_no','password');
            if($token = JWTAuth::attempt($credentials)){
                $user = Auth::user();
                $message = "Logged in successfully!!";
                $status = 200;
            }else{
                $token = '';
                $message = "Invalid credentials";
                $status = 401;
            }
        }catch (\Exception $e){
            $token = '';
            $message = "Fail";
            $status = 500;
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
        ];
        return response()->json($response,$status);
    }

}