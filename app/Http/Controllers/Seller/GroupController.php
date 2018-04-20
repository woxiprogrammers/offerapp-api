<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Seller;

use App\Customer;
use App\GroupCustomer;use App\User;
use App\Group;
use App\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class GroupController extends BaseController
{
    protected $perPage = 5;

    public function __construct()
    {
        $this->middleware('jwt.auth');
        if (!Auth::guest()) {
            $this->user = Auth::user();
        }
    }

    public function getGroupList(){
        try {
            $user = Auth::user();
            $seller_id= Seller::where('user_id',$user['id'])->pluck('id')->first();
            $seller_groups = Group::where('seller_id',$seller_id)->get();
            $iterator = 0;
            $groupList = array();
            foreach ($seller_groups as $key => $group) {
                $groupList[$iterator]['group_id'] = $group['id'];
                $groupList[$iterator]['group_name'] = $group['name'];
                $iterator++;
            }
            $data['select_groups'] = $groupList;
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Group Listing',
                'exception' => $e->getMessage(),
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $status);
    }

    public function addToGroup(Request $request){
        try{
            $user = Auth::user();
            $group_id = $request['group_id'];
            $mobile_no = $request ['mobile_no'];
            $user_id = User::where('mobile_no',$mobile_no)->pluck('id')->first();

            $customer_id = Customer::where('user_id',$user_id)->pluck('id')->first();

            $check_customer_group = GroupCustomer::where('customer_id', $customer_id)->where('group_id',$group_id)->pluck('id')->first();


            if($check_customer_group =="") {
                GroupCustomer::create([
                    'group_id' => $group_id,
                    'customer_id' => $customer_id
                ]);
                $message = 'Success';
                $status = 200;
            }else if($check_customer_group !=""){
                $message = 'User Already Exist';
                $status = 412;
            }
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Add To Group',
                'exception' => $e->getMessage(),
                'params' => $request->all()

            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

}