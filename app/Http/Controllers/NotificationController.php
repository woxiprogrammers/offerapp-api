<?php
    /**
     * Created by PhpStorm.
     * User: harsha
     * Date: 30/5/18
     * Time: 11:18 AM
     */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class NotificationController extends  BaseController{

    public function __construct(){
        $this->middleware('jwt.auth');
        if (!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function saveToken(Request $request){
        try{
            $user = Auth::user();
            $user->update(['expo_token' => $request['expo_token']]);
            $message = "Token saved successfully";
            $status = 200;
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Save Token',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message
        ];
        return response()->json($response,$status);
    }
}