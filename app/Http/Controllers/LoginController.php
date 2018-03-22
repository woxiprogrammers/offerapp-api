<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 22/3/18
 * Time: 3:18 AM
 */

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public $successStatus = 200;

    public function login(Request $request){
        try{
            $user = User::where($request->all())->first();
            if($user != null){
                $token =  $user->createToken('OfferApp')->accessToken;
                return response()->json(['token' => $token,
                    'StatusCode' => $this->successStatus],
                    $this->successStatus);
            }else{
                return response()->json(['error'=>'Unauthorised','StatusCode' => 401], 401);

            }
        }catch (\Exception $e){
            $data = [
                'action' => 'Login API',
                'params' => $request->all(),
                'exception' => $e->getMessage(),
            ];
            return $data;
            // Log::critical(json_encode($data));
        }
    }

    public function changePassword(Request $request){
        try{
            dd(Auth::guard('api')->user());
            if(Auth::attempt(['mobile_no' => Auth::user()->mobile_no , 'password' => request('currentPassword')]) ){
                $user = Auth::user();
                $user->password = bcrypt($request['newPassword']);
                $user->save();

                return response()->json(['success' => 'Password changed Successfully',
                    'StatusCode' => $this->successStatus],
                    $this->successStatus);
            }else{
                return response()->json(['error'=>'Incorrect confirm password','StatusCode' => 401], 401);
            }


        }catch (\Exception $e){
            $data = [
                'action' => 'Set Password',
                'params' => $request->all(),
                'exception' => $e->getMessage()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
    }
}