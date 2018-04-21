<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 22/3/18
 * Time: 10:32 AM
 */

namespace App\Http\Controllers\Auth;

use App\Customer;
use App\Role;
use App\Seller;
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
           /* $this->validate($request, [
                'first_name' => 'string|max:255',
                'last_name' => 'string|max:255',
                'email' => '    string|email|max:255|unique:users,email,',
                'password' => 'required|string|min:6',
                'mobile_no' => 'required|regex:/[0-9]/|unique:users,mobile_no,',
            ]);*/

/*            $user = $this->createUser($request->all());*/

            $credentials = $request->only('mobile_no','password');
            if($token = JWTAuth::attempt($credentials)){
                $user = Auth::user();
                $userData['firstName'] = $user['first_name'];
                $userData['lastName'] = $user['last_name'];
                $userData['email'] = $user['email'];
                $userData['mobileNo'] = ($user['mobile_no'] != null) ? $user['mobile_no'] : '';
                $userData['profilePic'] = ($user['profile_picture'] == null) ? '/uploads/user_profile_male.jpg' : env('OFFER_IMAGE_UPLOAD').$user['profile_picture'];
                $message = "Register in successfully!!";
                $status = 200;
                $user_data = [
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'mobileNo' => $user->mobile_no,
                    'profilePic' => env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$user->profile_picture,
                ];
            }else{
                $token = '';
                $user_data = '';
                $message = "Invalid credentials";
                $status = 401;
            }
            $response = [
                'message' => $message,
                'token' => $token,
                'userData' => $user_data
            ];

        }
        catch (\Exception $e){

            $token = '';
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Register API',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            $response = [
                'message' => $message,
                'token' => $token,
                'data' => $data
            ];
        }
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

    protected function createUser(array $input)
    {
        try{
            $role_slug = $input['roleSlug'];
            $role_id = Role::where('slug', $role_slug)->pluck('id')->first();

            $user = User::create([
                'role_id' => $role_id,
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'mobile_no' => $input['mobile_no'],
                'email' => $input['email'],
                'profile_picture' => '',
                'password' => Hash::make($input['password']),
            ]);
            if($role_slug == 'customer'){
                Customer::create([
                    'user_id' => $user->id
                ]);
            }elseif($role_slug == 'seller'){
                Seller::create([
                    'user_id' => $user->id
                ]);
            }
            return $user;
        }catch(\Exception $e){
            $data = [
                'action' => 'create user',
                'exception' => $e->getMessage(),
                'params' => $input
            ];
            Log::critical(json_encode($data));
        }

    }



}