<?php

namespace App\Http\Controllers\API;

use App\Category;
use App\City;
use App\HPContactUS;
use App\Offers;
use App\Order;
use App\OrderProducts;
use App\OrderProductsFeature;
use App\Products;
use App\ProductsFeature;
use App\Restaurant;
use App\RestaurantReview;
use App\Setting;
use App\SubCategory;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Lcobucci\JWT\Parser;
use App\Http\Controllers\Dashboard\Common;
use Illuminate\Support\Facades\Input;

class HomePageController extends Controller
{

    public function setting()
    {
        $items = Setting::orderby("id", "asc")->first();
        return parent::successjson($items, 200);
    }

    public function category()
    {
        $items = Category::orderby("id", "asc")->get();
        return parent::successjson($items, 200);
    }

    public function restaurant(Request $request)
    {
        $items = Restaurant::with('user');
        $city = Input::get('city_id');
        $cat = $request->category_id;
        $items = $items->whereHas("City", function ($q) use ($city) {
            if ($city) {
                $q->where("restaurant_city", $city);
            }
        });

        $items = $items->whereHas("Category", function ($q) use ($cat) {
            if ($cat) {
                $q->where("restaurant_category", $cat);
            }
        });
        if (!empty($city) && empty($cat)) {
            $items = $items->orderby("all_priority", "asc");
        } else {
            $items = $items->orderby("priority", "asc");
        }
        $items = $items->with("Category")->get();
        foreach ($items as $key => $item) {
            $item->total_rating = RestaurantReview::where('restaurant_id', $item->id)->avg('star');
        }
        return parent::successjson($items, 200);
    }

    public function restaurant_view(Request $request)
    {
        $items = Restaurant::orderby("id", "asc")->where("id", $request->id);
        $items = $items->with("Products");
        $items = $items->with("Products");
        $items = $items->first();
        return parent::successjson($items, 200);
    }

    public function sub_category()
    {
        $items = SubCategory::orderBy('priority')->orderBy("id", "asc")->first();
        return parent::successjson($items, 200);
    }


    public function offers(Request $request)
    {
        if ($request->city_id) {
            $offers = Offers::with(['category', 'city', 'restaurant', 'products'])
                ->where('city_id', $request->city_id)
                ->orderby("priority", "asc")
                ->get();
        } else {
            $offers = Offers::with(['category', 'city', 'restaurant', 'products'])
                ->orderby("priority", "asc")
                ->get();
        }
        foreach ($offers as &$item) {
            $item->restaurant->name = $item->restaurant->User->name;
            unset($item->restaurant->User);
        }
        return parent::successjson($offers, 200);
    }


    public function city()
    {
        $items = City::orderBy('priority')->orderby("id", "asc")->get();
        return parent::successjson($items, 200);
    }

    public function setting_contacts()
    {
        $items = HPContactUS::orderby("id", "asc")->first();
        return parent::successjson($items, 200);
    }

    public function cart_save(Request $request)
    {
        $validation = Validator::make($request->all(), $this->add_cars1());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }

        $restaurant = Restaurant::where("id", $request->restaurant_id)->first();

        if ($restaurant == null) {
            return parent::errorjson("Failer Created. Invalid restaurant", 400);
        }

        $city_id = City::where("id", $request->city_id)->first();

        if ($city_id == null) {
            return parent::errorjson("Failer Created Invalid city", 400);
        }

        $save = Order::where("ip", parent::IP_Address())->where("status", 0)->first();
        if ($request->order_id) {
            $save = Order::where('id', $request->order_id)->first();
        }

        if ($save == null) {
            $save = new Order();
            $save->client_name = "";
            $save->restaurant_id = $request->restaurant_id;
            $save->user_id = $request->user_id;
            $save->order_id = uniqid();
            $save->city_id = $request->city_id;
            $save->ip = parent::IP_Address();
            $save->phone_active = 0;
            $save->phone = "";
            $save->total = 0;
            $save->status = 0;
            $save->payment_type = $request->payment_type;
            $save->log = $request->address["log"];
            $save->lat = $request->address["lat"];
            $save->is_pickup = $request->self_pickup == 1;
            $save->save();
        }
        $order_id = $save->id;
        OrderProducts::where('order_id', $save->id)->delete();
        $price = 0;
        $feature = 0;
        $products = $request->products;
        foreach ($products as $product) {
            $order_products = new OrderProducts();
            $order_products->order_id = $order_id;
            $order_products->restaurant_id = $request->restaurant_id;
            $order_products->products_id = $product['products_id'];
            $order_products->qun = $product['qun'];
            $order_products->price = $product['amount'];
            $order_products->total = $product['amount'] * $product['qun'];
            $order_products->special_request = isset($product["special_request"]) ? $product["special_request"] : "";
            $order_products->save();
            $price += $product['amount'] * $product['qun'];

            //save new price
            if ($product['feature'] != 0) {
                if (count($product['feature']) != 0) {
                    foreach ($product['feature'] as $key => $value) {
                        $order_products_feature = new OrderProductsFeature();
                        $order_products_feature->order_products_id = $order_products->id;
                        $order_products_feature->products_feature_id = $value;
                        $order_products_feature->save();

                        $fr = ProductsFeature::where("id", $value)->first();
                        if ($fr == null) {
                            return parent::errorjson("Failer Created", 400);
                        }
                        $feature = $feature + $fr->amount;
                    }
                }
            }
        }
        $save->total = $price + $feature + ($request->self_pickup == 1 ? 0 : $restaurant->fees);
        $save->save();
        $data = array(
            "description" => "Order Placed Successfully",
            "order_id" => $order_id
        );

        return parent::successjson($data, 200);
    }


    private function add_cars1()
    {
        return [
            'city_id' => 'required|numeric|min:1',
            'restaurant_id' => 'required|numeric|min:1',
            'payment_type' => 'required|numeric|in:1,2,3',
            'address' => 'required',
        ];
    }

    public function cart_save_address(Request $request)
    {
        $validation = Validator::make($request->all(), $this->add_cars12());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }
        $save = Order::where("ip", parent::IP_Address())->where("status", 0)->first();

        if ($save == null) {
            return parent::errorjson("Failer Created", 400);
        }
        $save->log = $request->log;
        $save->lat = $request->lat;
        $save->save();

        return parent::successjson("Done Created", 200);
    }


    private function add_cars12()
    {
        return [
            'log' => 'required|min:1',
            'lat' => 'required|min:1',
        ];
    }

    public function payment_type(Request $request)
    {
        $validation = Validator::make($request->all(), $this->add_cars122());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }
        $save = Order::where("ip", parent::IP_Address())->where("status", 0)->first();

        if ($save == null) {
            return parent::errorjson("Failer Created", 400);
        }
        $save->payment_type = $request->payment_type;
        $save->status = 1;
        $save->save();

        return parent::successjson("Done Created", 200);
    }


    private function add_cars122()
    {
        return [
            'payment_type' => 'required|numeric|in:1,2,3',
        ];
    }

    public function complete_order(Request $request)
    {
        $validation = Validator::make($request->all(), $this->complete_order2());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }
        // $save = Order::where("ip", parent::IP_Address())->where("status", 0)->first();
        $save = Order::where("id", $request->order_id)->where("status", "<", "2")->first();

        if ($save == null) {
            return parent::errorjson("Failer Created", 400);
        }
        $save->client_name = $request->name;
        $save->phone = $request->phone;
        $save->status = 1;
        $verifyCode = mt_rand(100000, 999999);
        $save->code = $verifyCode;

        $message = "Mr. " . $request->name . " Your one time Order verification code is " . $verifyCode . "\nThanks from using Sandwich Map.";

        Common::SendTextSMS($request->phone, $message);
        $save->phone_active = 1;
        $save->save();

        $this->sendEmail($save);

        return parent::successjson("Done Created", 200);
    }


    private function complete_order2()
    {
        return [
            'name' => 'required|string',
            'phone' => 'required|string',
            'order_id' => 'required',
        ];
    }

    public function complete_order_verfiy(Request $request)
    {
        $validation = Validator::make($request->all(), $this->complete_order_verfiy2());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }
        // $save = Order::where("ip", parent::IP_Address())->where("status", 1)->first();
        $save = Order::where("id", $request->order_id)->where("status", 1)->first();
        if ($save == null) {
            return parent::errorjson("Failer Created", 400);
        }

        if ($save->code != $request->code) {
            return parent::errorjson("Failer Code", 400);
        }

        $save->phone_active = 1;
        $save->status = 2;
        $save->code = null;
        $save->save();
        $message = "Dear " . $save->client_name . " Your order is proceeded successfully.\nExpect a Call from the restaurant shortly.\nOrder Number: " . $save->id . "\nTotal bill is: " . $save->total . "\nThank You for using Sandwich Map.";

        Common::SendTextSMS($save->phone, $message);

        $user = User::where('id', $save->user_id)->first();

        $message = "Dear Partner You Have One New Order 

                    Thank you for using SandwichMap 
                    
                    www.sandwichmap.com
                    
                    Thank you for using SandwichMap for more support Please call 0501212770
                    
                    Please do not reply to this email. 
                    
                    We would also be happy to receive your feedback - suggestions and complaints on 
                    
                    Management Email :
                        sandwichmap@yahoo.com
                    
                    Owner Email :
                        i.osmann@yahoo.com
                        fs.aljabri@yahoo.com
                    Sincerely,
                    
                    Sandwich Map LLC
                    Restaurants Partners Support Team
                    ";

        $msg = "Dear Partner You Have One New Order\nCustomer name: " . $save->client_name . "\nMobile Number: " . $save->phone . "\nTotal Bill: " . $save->total . "\nThank you for using Sandwich Map";

        Common::SendTextSMS($user->phone, $msg);

        return parent::successjson("Active Created", 200);
    }

    private function sendEmail($order)
    {
        $user = User::where('id', $order->user_id)->first();
        $to = $user->email;
        $subject = "SandwichMap - new Order# " . $order->id;
        $headers = "From:   noreply@icheck-antibody.jp";
        $msg = "Dear Partner You Have One New Order\nCustomer name: " . $order->client_name . "\nMobile Number: " . $order->phone . "\nTotal Bill: " . $order->total . "\nThank you for using Sandwich Map";
        Common::SendEmail($to, $subject, $msg, $headers);

        if ($user->sub_emails) {
            $emails = explode(',', $user->sub_emails);
            foreach ($emails as $e) Common::SendEmail($e, $subject, $msg, $headers);
        }
    }

    private function complete_order_verfiy2()
    {
        return [
            'code' => 'required|string',
            'order_id' => 'required'
        ];
    }

    public function restaurant_comment(Request $request)
    {
        $validation = Validator::make($request->all(), $this->restaurant_comment2());
        if ($validation->fails()) {
            return parent::errorjson($validation->errors(), 400);
        }
        $save = Restaurant::where("id", $request->restaurant_id)->first();

        if ($save == null) {
            return parent::errorjson("Failer Created", 400);
        }

        $save2 = new RestaurantReview();
        $save2->restaurant_id = $request->restaurant_id;
        $save2->comment = $request->comment;
        $save2->client_name = $request->client_name;
        $save2->client_phone = $request->client_phone;
        $save2->star = $request->star;
        $save2->save();

        return parent::successjson("Commented Created", 200);
    }


    private function restaurant_comment2()
    {
        return [
            'comment' => 'required|string',
            'client_name' => 'required|string',
            'client_phone' => 'required|string',
            'restaurant_id' => 'required',
            'star' => 'required|in:1,2,3,4,5',
        ];
    }

    public function client_comment(Request $request)
    {
        $items = RestaurantReview::orderby("id", "asc")->where("client_name", $request->client_name)->get();
        return parent::successjson($items, 200);
    }

    public function createPosOrder(Request $request)
    {
        $this->validate($request, [
            'restaurant_id' => 'required|exists:restaurant,id',
            'payment_type' => 'required',
            'products' => 'required'
        ]);
        $restaurant = Restaurant::where('id', $request->restaurant_id)->first();

        if (!$restaurant) {
            return response()->json(['error' => 'Invalid restaurant'], 400);
        }
        $order = Order::where('id', $request->order_id)->first();
        if (!$order) {
            $order = Order::create([
                'order_id' => uniqid(),
                'client_name' => $request->name ?? '-----',
                'phone' => $request->phone ?? '-----',
                'ip' => parent::IP_Address(),
                'phone_active' => 1,
                'total' => 0,
                'status' => 2,
                'payment_type' => $request->payment_type,
                'city_id' => $restaurant->restaurant_city,
                'restaurant_id' => $restaurant->id,
                'user_id' => $restaurant->user_id,
                'code' => 0,
                'b_view' => 1,
                'is_pickup' => 1
            ]);
        } else {
            $order->update([
                'client_name' => $request->name ?? '-----',
                'phone' => $request->phone ?? '-----',
                'ip' => parent::IP_Address(),
                'phone_active' => 1,
                'total' => 0,
                'status' => 2,
                'payment_type' => $request->payment_type,
                'code' => 0,
                'b_view' => 1
            ]);
        }
        OrderProducts::where('order_id', $order->id)->delete();

        $price = 0;
        $feature = 0;
        foreach ($request->products as $product) {
            $order_product = OrderProducts::create([
                'order_id' => $order->id,
                'restaurant_id' => $order->restaurant_id,
                'products_id' => $product['products_id'],
                'price' => $product['amount'],
                'qun' => $product['qun'],
                'amount' => $product['amount'],
                'total' => $product['amount'] * $product['qun'],
                'special_request' => $product['special_request'] ?? ''
            ]);
            $price += $product['amount'] * $product['qun'];

            if ($product['feature'] != 0 && count($product['feature']) != 0) {
                foreach ($product['feature'] as $key => $value) {
                    if (ProductsFeature::where('id', $value)->count() <= 0) continue;
                    OrderProductsFeature::create([
                        'order_products_id' => $order_product->id,
                        'products_feature_id' => $value
                    ]);
                    $fr = ProductsFeature::where('id', $value)->first();
                    $feature += $fr->amount;
                }
            }
        }
        $order->update(['total' => $price + $feature]);

        return response()->json(['description' => 'Order Placed Successfully', 'order_id' => $order->id]);
    }
}
