<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 28/3/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Seller;

use App\Customer;
use App\GroupCustomer;
use App\GroupMessage;
use App\Offer;
use App\User;
use App\Group;
use App\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use Mockery\Exception;

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
            $groups = Group::where('seller_id',$seller_id)->paginate($this->perPage);
            $groupList = array();
            foreach ($groups as $key => $group) {
                $groupList[$key]['group_id'] = $group['id'];
                $groupList[$key]['group_name'] = $group['name'];
                $groupList[$key]['total_member'] = $group->groupCustomer->count();
            }
            $data = [
                'select_group' => $groupList,
                'pagination' => [
                    'page' => $groups->currentPage() ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($groups),
                    'totalCount' => $groups->total(),
                ],
            ];
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

    public function addMemberToGroup(Request $request){
        try{
            $group_id = $request['group_id'];
            $mobile_no = $request ['mobile_no'];
            $user_id = User::where('mobile_no',$mobile_no)->pluck('id')->first();

            $customer_id = Customer::where('user_id',$user_id)->pluck('id')->first();

            $check_customer_group_count = GroupCustomer::where('customer_id', $customer_id)->where('group_id',$group_id)->pluck('id')->count();

            if($check_customer_group_count > 0) {
                $message = 'User Already Exist';
                $status = 412;
            }else{
                GroupCustomer::create([
                    'group_id' => $group_id,
                    'customer_id' => $customer_id
                ]);
                $message = 'Success';
                $status = 200;
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
        }
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

    public function getGroupDetail(Request $request){
        try {
            $group_id = $request['group_id'];
            $customerIds = GroupCustomer::where('group_id', $group_id)->pluck('customer_id');
            $iterator = 0;
            $memberDetailList = array();
            foreach ($customerIds as $key => $customerId){
                $customerData = User::join('customers','customers.user_id','=','users.id')
                                    ->select('users.id','users.first_name','users.last_name','users.mobile_no','users.email')
                                    ->where('customers.id',$customerId)
                                    ->first();
                $memberDetailList[$iterator]['customer_id'] = $customerId;
                $memberDetailList[$iterator]['customer_name'] = $customerData['first_name'].' '.$customerData['last_name'];
                $memberDetailList[$iterator]['customer_mobile'] = $customerData['mobile_no'];
                $memberDetailList[$iterator]['customer_email'] = $customerData['email'];
                $iterator++;
            }
            $data['group_details'] = $memberDetailList;
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Group Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $status);
    }

    public function groupOfferListing(Request $request){
        try{
            $group_id = $request['group_id'];
            $group_offers_id = GroupMessage::where('group_id', $group_id)->pluck('offer_id');
            $iterator = 0;
            $offerList = array();


            foreach ($group_offers_id as $key => $group_offer_id) {
                $offers = Offer::where('id', $group_offer_id)->get();


                foreach ($offers as $key2 => $offer) {
                    $offerList[$iterator]['offer_id'] = $offer['id'];
                    $offerList[$iterator]['offer_type_id'] = $offer['offer_type_id'];
                    $offerList[$iterator]['offer_type_name'] = $offer->offerType->name;
                    $offerList[$iterator]['offer_status_id'] = $offer['offer_status_id'];
                    $offerList[$iterator]['offer_status_name'] = $offer->offerStatus->name;
                    $offerList[$iterator]['offer_description'] = $offer->description;
                    $valid_from = $offer->valid_from;
                    $valid_to = $offer->valid_to;
                    $offerList[$iterator]['start_date'] = date('d F, Y', strtotime($valid_from));
                    $offerList[$iterator]['end_date'] = date('d F, Y', strtotime($valid_to));
                    $iterator++;
                }
            }
            $currentPage = Input::get('page', 1)-1;
            $group_offer_array = collect($offerList)->toArray();
            $pagedData = array_slice($group_offer_array, $currentPage * $this->perPage, $this->perPage);

            $data = [
                'group_offers' => $pagedData,
                'pagination' => [
                    'page' => $currentPage + 1 ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($pagedData),
                    'totalCount' => count($offerList),
                ],
            ];
            $message = 'Success';
            $status = 200;
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Group-Offer-Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
            'data' => $data
        ];
        return response()->json($response, $status);
    }

    public function createGroup(Request $request){
        try{
            $user = Auth::user();
            $input = $request->all();
            $seller_id = Seller::where('user_id',$user['id'])->pluck('id')->first();
            $groupCount = Group::where('seller_id',$seller_id)->where('name',$input['group_name'])->count();
            if($groupCount > 0){
                $message = 'Group Name Already Exist';
                $status = 412;

            }else{
                Group::create([
                    'name' => $input['group_name'],
                    'description' => $input['description'],
                    'seller_id' => $seller_id
                ]);
                $message = 'Group created successfully';
                $status = 200;
            }
        } catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Create Group',
                'exception' => $e->getMessage(),
                'params' => $request->all()

            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

    public function promoteOffer(Request $request){
        try{
            $user = Auth::user();
            $offer_id = $request['offer_id'];

            $role_id = User::where('id',$user->id)->pluck('role_id')->first();
            foreach($request['group_id'] as $key => $groupId){
                GroupMessage::create([
                    'group_id' => $groupId,
                    'role_id' => $role_id,
                    'offer_id' => $offer_id,
                    'reference_member_id' => $user->id
                ]);
            }

            $message = 'Offer Promoted Successfully';
            $status = 200;
        }catch (\Exception $e) {
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Promote Offer',
                'exception' => $e->getMessage(),
                'params' => $request->all()

            ];
            Log::critical(json_encode($data));
        }
        $response = [
            'message' => $message,
        ];
        return response()->json($response, $status);
    }

}