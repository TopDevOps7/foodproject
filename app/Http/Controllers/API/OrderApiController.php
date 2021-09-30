<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Order;
use App\OrderProducts;
use App\OrderProductsFeature;
use App\Http\Controllers\Common;
use App\User;
use App\Events\StatusLiked;

class OrderApiController extends Controller
{
    public function StoreOrder(Request $request)
    {
        $orderFoods = $request->orderFoods;
        $product_id = '';
        if($orderFoods) {
            $order = new Order();
            $order->phone = $request->orderMobile;
            $order->order_id = $request->orderId;
            $order->lat = $request->orderLat;
            $order->log = $request->orderLon;
            $order->total = $request->totalBill;
            $order->payment_type = $request->orderPayment;
            $order->created_at = $request->orderDate;
            $order->updated_at = $request->orderDate;
            $order->city_id = $request->orderCity;
            $order->client_name = $request->orderClientName;
            $order->restaurant_id = $request->foodRestaurantId;
            $order->user_id = $request->orderUserId;
            $order->save();
            for($i = 0; $i < count($orderFoods); $i++)
            {
                $food = $orderFoods[$i];
                $product = new OrderProducts();
                $product->price = $food['foodPrice'];
                $product->qun = $food['foodCount'];
                $product->restaurant_id = $request->foodRestaurantId;
                $product->products_id = $food['foodId'];
                $product->total = $food['foodTotal'];
                $product->order_id = $order->id;
                $product->created_at = $request->orderDate;
                $product->updated_at = $request->orderDate;
                $product->save();
                if(key_exists('foodFeatures', $food)) {
                    $features = $food['foodFeatures'];
                    $featureArray = explode('-', $features);
                    if(!empty($featureArray)) {
                        foreach($featureArray as $id) {
                            $productFeature = new OrderProductsFeature();
                            $productFeature->order_products_id = $product->id;
                            $productFeature->products_feature_id = $id;
                            $productFeature->created_at = $request->orderDate;
                            $productFeature->updated_at = $request->orderDate;
                            $productFeature->save();
                        }

                    }
                }

            }
                $user = User::where('id', $order->user_id)->first();
                
                $to = $user->email;
                $subject = "SandwichMap - new Order# ".$order->id;
                $message = "You have new order
                            Order# ".$order->id.
                            '
                            Please login to your restaurant dashboard and precess the order.
                            It’s your chance to show your customer the best you can do 
                            Good Luck 
                            Sincerely,
                            Your partner 
                            Sandwich Map Support Team';
                $headers = "From:   noreply@icheck-antibody.jp" . "\r\n" .
                            "CC: somebodyelse@example.com";
                Common::SendTextSMS($user ->phone, $message);
                Common::SendEmail($to,$subject,$message,$headers);
                event(new StatusLiked('hello world'));
                
            return response()->json(["order_id"=>$order->id]);
            
        }
        else {
            return response()->json(["fail"=>"Invalid request"]);
        }

    }

    public function SendVerifySMS(Request $request)
    {
        $orderMobile = $request->orderMobile;
        $orderID = $request->orderID;
        $orderUserName = $request->orderUserName;

        $verifyCode =mt_rand(100000, 999999);

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'http://api.smscountry.com/SMSCwebservice_bulk.aspx', [
            'form_params'  => [
                "User"         => "Sandwichmap",
                "passwd"       => "10495477",
                "mobilenumber" => $orderMobile,
                "message"      => "Hello ".$orderUserName." Your verification code to order is ".$verifyCode,
                "sid"          => "AD-Telebu",
                "mtype"        => "N",
                "DR"           => "Y",
            ]
        ]);

        if ($response->getStatusCode() === 200) {

            /*DB::table('phone_verify')
                            ->insert([
                                    'phone' => $orderMobile,
                                    'order_id' => $orderID,
                                    'order_username' => $orderUserName,
                                    'verify_code' => $verifyCode
                                ]);*/
            return response()->json(["code"=>$verifyCode]);
        } else {
            return response()->json("no");
        }
    }

    public function CheckVerifyCode(Request $request)
    {
        $orderMobile = $request->orderMobile;
        $orderID = $request->orderID;
        $orderverifycode =  $request->orderverifycode;

        $exist_vefiry_code = DB::table('phone_verify')->where('order_id', $orderID)->get();

        if($verifyCode == $exist_vefiry_code[0]->verify_code) {
            return response()->json("ok");
        }else{
            return response()->json("no");
        }

    }

}

