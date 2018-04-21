<?php
/**
 * Created by PhpStorm.
 * User: arvind
 * Date: 16/4/18
 * Time: 5:58 PM
 */

namespace App\Http\Controllers\Customer;

use App\Customer;
use App\Group;
use App\GroupCustomer;
use App\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;

class GroupController extends BaseController
{
    protected $perPage = 5;
    public function __construct(){
        $this->middleware('jwt.auth');
        if(!Auth::guest()) {
            $this->user = Auth::user();
        }
    }
    public function getGroupList(Request $request){
        try{
            $user_id = Auth::user()->id;
            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();
            $groups = array();
            $customer_groups = GroupCustomer::where('customer_id',$customer_id)->get();
            foreach($customer_groups as $key => $customer_group){
                    $groups[$key]['groupId'] = $customer_group->group->id;
                    $groups[$key]['groupName'] = $customer_group->group->name;
                }

                $data = [
                    'records' => $groups
                ];


        }catch(\Exception $e){

            $data = [
                'action' => 'Get Customer Group List',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));

        }
        return response()->json($data);
    }

    public function getGroupOffers(Request $request){
        try{

            $group_id = $request['groupId'];
            $offers = array();

            $seller_group_id = Group::where('id',$group_id)->pluck('seller_id')->first();
            $group_messages = GroupMessage::where('reference_member_id',$seller_group_id)
                ->paginate($this->perPage);

            foreach ($group_messages as $key => $group_message){
                $offers[$key]['offerId'] = $group_message->offer->id;
                $offers[$key]['offerName'] = $group_message->offer->offerType->name;
                $offers[$key]['offerPic'] = env('WEB_PUBLIC_PATH').env('OFFER_IMAGE_UPLOAD').$group_message->offer->offerImages->first()->name;
                $offers[$key]['sellerInfo'] = $group_message->offer->sellerAddress->seller->user->first_name.' '.$group_message->offer->sellerAddress->seller->user->last_name;
                $valid_to = $group_message->offer->valid_to;
                $offers[$key]['offerExpiry']= date('d F, Y',strtotime($valid_to));
            }
            $data = [
                'records' => $offers,
                'pagination' => [
                    'page' => $group_messages->currentPage(),
                    'perPage' => $this->perPage,
                    'pageCount' => $group_messages->count(),
                    'totalCount' => $group_messages->total(),
                ],
            ];
        }catch(\Exception $e){

            $data = [
                'action' => 'Get Group Offers',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));

        }
        return response()->json($data);
    }

    public function leaveGroup(Request $request){
        try{
            $user_id = Auth::user()->id;
            $group_id = $request['groupId'];

            $customer_id = Customer::where('user_id', $user_id)->pluck('id')->first();

            $group_customer = GroupCustomer::where('customer_id',$customer_id)
                ->where('group_id',$group_id)
                ->delete();
            if($group_customer > 0 ){
                $data = [
                    'removed' => true
                ];
            }else{
                $data = [
                    'removed' => false
                ];
            }

        }catch(\Exception $e){

            $data = [
                'action' => 'Remove Group',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
        }
        return response()->json($data);
    }

}