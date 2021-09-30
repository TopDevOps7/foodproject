<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use App\Restaurant;
use App\RestaurantReview;
use App\Products;
use App\ProductsCategory;
use App\Http\Controllers\Dashboard\Common;

class RestaurantApiController extends Controller
{
    public function GetRestaurant()
    {
        $restaurants = Restaurant::with('category')->with('city')->with('user')->get();
        return response()->json($restaurants, 200);
    }
    // Zita added
    public function SearchRestaurant(Request $request)
    {
        $search = $request->search_text;
        $city_id = $request->city_id;
        $restaurants = Restaurant::with('user')->whereHas('user', function ($query) use ($search) {
            $query->where('name', 'like', "%$search%");
        })->where('restaurant_city', $city_id)->get();
        return response()->json($restaurants, 200);
    }
    // Zita added

    public function GetProduct($id)
    {
        $products = Products::with('productsfeature')->where('restaurant_id', $id)->get();

        return response()->json($products, 200);
    }

    public function GetProductSearch(Request $request)
    {
        $search = $request->search;
        if ($request->city_id) {
            $city_id = $request->city_id;
            $products = Products::with('productsfeature')->whereHas("Restaurant", function ($q) use ($city_id) {
                if ($city_id) {
                    $q->where("restaurant_city", $city_id);
                }
            })->where('name', 'like', "%$search%")->get();
        } else {
            $products = Products::with('productsfeature')->where('name', 'like', "%$search%")->get();
        }
        foreach ($products as $product) {
            $product->restaurant_name = $product->Restaurant->name;
        }
        return response()->json($products, 200);
    }

    public function GetRestaurantCategory($id)
    {
        $restaurant = Restaurant::with('user')->where('id', $id)->first();
        $restaurant->total_rating = RestaurantReview::where('restaurant_id', $id)->avg('star');
        $comment = RestaurantReview::where('restaurant_id', $id)->get();
        $product_ids = Products::where('restaurant_id', $id)->pluck('id');
        $product_sub_cats = ProductsCategory::with('subcategory')->whereIn('products_id', $product_ids)->orderBy('sub_category_id')->get();
        $products = [];
        if (count($product_sub_cats) > 0) {
            foreach ($product_sub_cats as $sub) {
                array_push($products, [
                    'sub_cat_name' => $sub['subcategory']['name'],
                    'sub_cat_id' => $sub['subcategory']['id'],
                    'products' => Products::with('productsfeature')->where('restaurant_id', $id)->where('id', $sub['products_id'])->get()
                ]);
            }
        }

        return response()->json(compact('restaurant', 'comment', 'products'), 200);
    }

    public function GetVerifySMS($phone)
    {
        $code = rand(100000, 999999);
        $message = "Please verify your order using code " . $code;
        Common::SendTextSMS($phone, $message);
        return json_encode(["code" => $code]);
    }
}
