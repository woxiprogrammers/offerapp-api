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
use App\Http\Controllers\CustomTraits\NotificationTrait;
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
use NotificationChannels\ExpoPushNotifications\ExpoChannel;

class GroupController extends BaseController
{
    use NotificationTrait;
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
            $memberDetailList = array();
            $customers = Customer::whereIn('id', $customerIds)->paginate($this->perPage);
            foreach ($customers as $key => $customer){
                $user = $customer->user;
                $memberDetailList[$key]['customer_id'] = $user->id;
                $memberDetailList[$key]['customer_name'] = $user->first_name.' '.$user->last_name;
                $memberDetailList[$key]['customer_mobile'] = $user->mobile_no;
                $memberDetailList[$key]['customer_email'] = $user->email;
            }
            $data = [
                'group_details' => $memberDetailList,
                'pagination' => [
                    'page' => $customers->currentPage() ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($customers),
                    'totalCount' => $customers->total(),
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
            $group_offers= GroupMessage::where('group_id', $group_id)->paginate($this->perPage);
            $offerList = array();


            foreach ($group_offers as $key => $group_offer) {
                $offer = $group_offer->offer;

                    $offerList[$key]['offer_id'] = $offer['id'];
                    $offerList[$key]['offer_type_id'] = $offer['offer_type_id'];
                    $offerList[$key]['offer_type_name'] = $offer->offerType->name;
                    $offerList[$key]['offer_status_id'] = $offer['offer_status_id'];
                    $offerList[$key]['offer_status_name'] = $offer->offerStatus->name;
                    $offerList[$key]['offer_description'] = $offer->description;
                    $valid_from = $offer->valid_from;
                    $valid_to = $offer->valid_to;
                    $offerList[$key]['start_date'] = date('d F, Y', strtotime($valid_from));
                    $offerList[$key]['end_date'] = date('d F, Y', strtotime($valid_to));
                }


            $data = [
                'group_offers' => $offerList,
                'pagination' => [
                    'page' => $group_offers->currentPage() ,
                    'perPage' => $this->perPage,
                    'pageCount' => count($offerList),
                    'totalCount' => $group_offers->total(),
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
$customer = User::where('id',5)->first();
            $this->sendNotification($customer);
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
