<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 22/3/18
 * Time: 10:32 AM
 */

namespace App\Http\Controllers\Auth;

use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class RegisterController extends BaseController
{

    public function __construct()
    {
        $this->middleware('jwt.auth',['except' => ['register', 'getOtp', 'validator']]);
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function register(Request $request)
    {
        try{

            $input = $request->all();

            /*$this->validate($request, [
                'first_name' => 'string|max:255',
                'last_name' => 'string|max:255',
                'email' => '    string|email|max:255|unique:users,email,',
                'password' => 'required|string|min:6',
                'mobile_no' => 'required|regex:/[0-9]/|unique:users,mobile_no,',
            ]);*/

            $role_id = $request['role'];

            User::create([
                'role_id' => $role_id,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'mobile_no' => $input['mobile_no'],
                'email' => $input['email'],
                'profile_picture' => 'avatar9.jpg',
                'password' => Hash::make($input['password']),
            ]);

            $credentials = $request->only('mobile_no','password');
            if($token = JWTAuth::attempt($credentials)){
                $user = Auth::user();
                $message = "Register in successfully!!";
                $status = 200;
            }else{
                $token = '';
                $message = "Invalid credentials";
                $status = 401;
            }

        }
        catch (\Exception $e){
            if ($e instanceof ValidationException){
                $errors = $e->response;

                return response()->json($errors->original);
            }
            $data = [
                'action' => 'Register API',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];

            Log::critical(json_encode($data));
        }

        $response = [
            'message' => $message,
            'token' => $token,
        ];
        return response()->json($response, $status);

    }

        public function getOtp(Request $request){
            $apiKey = urlencode(env('SMS_KEY'));

            // Message details
            $numbers = array($request['mobile_no']);
            $sender = urlencode('TXTLCL');
            $code = str_random(6);
            $message = rawurlencode('Your OTP is '.$code);

            $numbers = implode(',', $numbers);

            // Prepare data for POST request
            $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

            // Send the POST request with cURL
            $ch = curl_init('https://api.textlocal.in/send/');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            // Process your response here
            return $response;

        }

}