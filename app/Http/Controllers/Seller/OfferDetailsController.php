<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 26/3/18
 * Time: 2:29 PM
 */
<?php
/**
 * Created by PhpStorm.
 * User: sonali
 * Date: 21/3/18
 * Time: 5:38 PM
 */
namespace App\Http\Controllers\Seller;

use App\Offer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Routing\Controller as BaseController;

class OfferController extends BaseController{
    public function getOfferDetails(Request $request){
        try{
            $offerId = $request-> only(id);
            $offers = Offer::where('id' , $offerId)->get();
            $offerList = array();
            foreach($offers as $key => $offer){
                $offerList['offer_id'] = $offer['id'];
                $offerList['seller_address_id'] = $offer['seller_address_id'];
                $offerList['offer_type_id'] = $offer['offer_type_id'];
                $offerList['offer_type_name'] = $offer->offerType->name;
                $offerList['offer_status_id'] = $offer['offer_status_id'];
                $offerList['offer_status_name'] = $offer->offerStatus->name;
                $offerList['offer_description'] = $offer->description;
                $offerList['valid_from'] = $offer['valid_from'];
                $offerList['valid_to'] = $offer['valid_to'];

            }

            $data['offer_list'] = $offerList;
            $message = 'Success';
            $status = 200;
        }catch(\Exception $e){
            $message = "Fail";
            $status = 500;
            $data = [
                'action' => 'Seller Offer Listing',
                'exception' => $e->getMessage(),
                'params' => $request->all()
            ];
            Log::critical(json_encode($data));
            abort(500);
        }
        $response = [
            'message' => $message
        ];
        return response()->json($response,$status);
    }
}