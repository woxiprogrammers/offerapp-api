<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 22/3/18
 * Time: 10:32 AM
 */

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class RegisterController extends Controller
{

    public $successStatus = 200;

    public function __construct()
    {
        //
    }

    public function register(Request $request)
    {
        try{
            /*$errorPassword = 'true';
            $errorMobileNo = 'true';

           $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'mobile_no' => 'required|regex:/[0-9]/|unique:users,mobile,'.$request->id,
            'email' => 'email|max:255|unique:users,email,'.$request->id,
            'password' => 'required|min:6',
            'profile_picture' => 'image|mimes:jpg,png',
        ]);

        if ($validator->fails()) {
            if($validator->errors()->has('mobile_no')){
                $errorMobileNo = 'false';
            }
            if($validator->errors()->has('password')){
                $errorPassword = 'false';
            }
            return response()->json(['error'=>$validator->errors(),
                'errorMobileNo' => $errorMobileNo,
                'errorPassword' => $errorPassword,
                'StatusCode' => 401 ], 401);
        }*/
            $input = $request->all();
            $user = User::create([
                'role_id' => '3',
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'mobile_no' => $input['mobile_no'],
                'email' => $input['email'],
                'profile_picture' => $input['profile_picture'],
                'password' => $input['password'],
            ]);

            $success['token'] =  $user->createToken('offerApp')->accessToken;
            $success['name'] =  $user->first_name;

            return response()->json(['success'=>$success,
                'StatusCode' => $this->successStatus],
                $this->successStatus);

        }
        catch (\Exception $e){
            $data = [
                'action' => 'Register API',
                'exception' => $e->getMessage(),
            ];
            return $data;
            // Log::critical(json_encode($data));
        }

    }


}