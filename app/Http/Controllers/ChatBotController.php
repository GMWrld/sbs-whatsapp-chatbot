<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Branch;
use App\Models\OrderDetail;
use App\CentralLogics\Helpers;

//Session::flush();

class ChatBotController extends Controller
{
    //private $productArray = [50,20];
    private $products;
    private $branches;
    //private $depotArray = ["HeadQuarter","Arusha","Mwenge"];

    public function __construct()
    {
        $this->products = Product::all();  // Initialize $products in the constructor
        $this->branches = Branch::all();  
    }

    public function handleIncomingMessages(Request $request)
    {
        $userMessage = strtolower(trim($request->input('Body')));
        $responseMessage = '';


        if (Session::get('firstMessageFlag', true)) {
            $responseMessage = $this->handleFirstMessage($userMessage);
        } elseif (Session::get('secondMessageFlag', false)) {
            $responseMessage = $this->handleSecondMessage($userMessage);
        } elseif (Session::get('thirdMessageFlag', false)) {
            $responseMessage = $this->handleThirdMessage($userMessage);
        } elseif (Session::get('fourthMessageFlag', false)) {
            $responseMessage = $this->handleFourthMessage($userMessage);
        } elseif (Session::get('fifthMessageFlag', false)) {
            $responseMessage = $this->handleFifthMessage($userMessage);
        } elseif (Session::get('sixthMessageFlag', false)) {
            $responseMessage = $this->handleSixthMessage($userMessage);
        } elseif (Session::get('seventhMessageFlag', false)) {
            $responseMessage = $this->handleSeventhMessage($userMessage);
        } elseif (Session::get('eighthMessageFlag', false)) {
            $responseMessage = $this->handleEighthMessage($userMessage);
        } elseif (Session::get('ninthMessageFlag', false)) {
            $responseMessage = $this->handleNinthMessage($userMessage);
        } elseif (Session::get('trackingMessageFlag', false)) {
            $responseMessage = $this->handleTrackingMessage($userMessage);
        }

        $messageCreated = $this->sendMessage($responseMessage);

        return response($messageCreated, 200)->header('Content-Type', 'text/xml');
    }

    private function handleFirstMessage($userMessage)
    {
        if ($userMessage == 'hi' || $userMessage == 'hello' || $userMessage == '#') {
            Session::put('firstMessageFlag', false);
            Session::put('secondMessageFlag', true);
            return "Hello, Welcome to ORYX... How can we help you?\n".
                "1: Place an Order\n".
                "2: Track an Order";
        } else {
            return "Sorry, I can only reply to \"Hi\" and \"Hello\"";
        }
    }

    private function handleSecondMessage($userMessage)
    {
        switch ($userMessage) {
            case '1':
                Session::put('secondMessageFlag', false);
                Session::put('thirdMessageFlag', true);
                // return "What product do you want to place an order for:\n".
                // "1: Gas Tank ".$this->productArray[0]."-Tsh75,000/=\n".
                // "2: Gas Tank ".$this->productArray[1]."-Tsh35,000/=\n".
                // "#: Back to the main menu";
                $productList = "What product do you want to place an order for:\n";
                foreach ($this->products as $product) {
                    $productList .= $product['id'] . ": Gas Tank " . $product['name'] . "-Tsh" . $product['price'] . "/=\n";
                }
                $productList .= "#: Back to the main menu";

                return $productList;
                break;
            case '2':
                Session::put('secondMessageFlag', false);
                Session::put('trackingMessageFlag', true);
                return "Please enter your Order ID Below like 100034";
                break;
            case '#':
                Session::put('secondMessageFlag', false);
                Session::put('firstMessageFlag', true);
                return $this->handleFirstMessage($userMessage);
                break;
            default:
                return "I didn't understand your choice. Please try again or reply with # to go back to the main menu.";
        }
    }

    private function handleThirdMessage($userMessage)
    {
        switch ($userMessage) {
            case '1':
            case '2':
                Session::put('thirdMessageFlag', false);
                Session::put('fourthMessageFlag', true);
                Session::put('productSelected',Product::find(intval($userMessage)));
                // return "Placing an Order for Gas Tank ".$this->productArray[intval($userMessage) - 1]." kg...\n".
                //         "Please select the depot below:\n".
                //         "1: HeadQuarter\n".
                //         "2: Arusha\n".
                //         "3: Mwenge\n".
                //         "#: Back to the main menu";
                $branchList = "Placing an Order for " . Session::get('productSelected')->name . "\n" . "Please select the depot below:\n";

                foreach ($this->branches as $branch) {
                    // Skip the branch with id == 1
                    if ($branch->id == 1) {
                        continue;
                    }

                    $branchList .= $branch->id . ": " . $branch->name . "\n";
                }

                $branchList .= "#: Back to the main menu";

                return $branchList;
                break;
            case '#':
                Session::put('thirdMessageFlag', false);
                Session::put('secondMessageFlag', true);
                return $this->handleSecondMessage($userMessage);
                break;
            default:
                return "I didn't understand your choice. Please try again or reply with # to go back to the menu";
        }
    }

    private function handleFourthMessage($userMessage)
    {
        switch ($userMessage) {
            case '1':
            case '2':
            case '3':
                Session::put('fourthMessageFlag', false);
                Session::put('fifthMessageFlag', true);
                Session::put('depotSelected', Branch::find(intval($userMessage)));
                return  "Placing an Order at ".Session::get('depotSelected')->name." ...\n".
                        "Please enter the stock you need:\n".
                        "#: Back to the main menu";
                break;
            case '#':
                Session::put('fourthMessageFlag', false);
                Session::put('thirdMessageFlag', true);
                return $this->handleThirdMessage($userMessage);
                break;
            default:
                return "I didn't understand your choice. Please try again or reply with # to go back to the menu";
        }
    }

    private function handleFifthMessage($userMessage)
    {
        if($userMessage != '#'){
            Session::put('fifthMessageFlag', false);
            Session::put('sixthMessageFlag', true);
            Session::put('stockSelected', intval($userMessage));
            return  "Please enter your Full Name Below:\n".
                    "#: Back to the main menu";
        } else{
            Session::put('fifthMessageFlag', false);
            Session::put('fourthMessageFlag', true);
            return $this->handleFourthMessage($userMessage);
        }
    }

    private function handleSixthMessage($userMessage)
    {
        if($userMessage != '#'){
            Session::put('sixthMessageFlag', false);
            Session::put('seventhMessageFlag', true);
            Session::put('fullName', $userMessage);
            return  "Please enter your Phone Number Below:\n".
                    "#: Back to the main menu";
        } else{
            Session::put('sixthMessageFlag', false);
            Session::put('fifthMessageFlag', true);
            return $this->handleFifthMessage($userMessage);
        }
    }

    private function handleSeventhMessage($userMessage)
    {
        if($userMessage != '#'){
            Session::put('seventhMessageFlag', false);
            Session::put('eighthMessageFlag', true);
            Session::put('phoneNumber', $userMessage);
            return  "Please enter your Address Below, make sure to be Specific and provide all the Details:\n".
                    "#: Back to the main menu";
        } else{
            Session::put('seventhMessageFlag', false);
            Session::put('sixthMessageFlag', true);
            return $this->handleSixthMessage($userMessage);
        }
    }

    private function handleEighthMessage($userMessage)
    {
        if($userMessage != '#'){
            Session::put('eighthMessageFlag', false);
            Session::put('ninthMessageFlag', true);
            Session::put('address', $userMessage);
            $tax_amount = (Helpers::tax_calculate(Session::get('productSelected'), Session::get('productSelected')->price)) * Session::get('stockSelected');

            return  "Receipt:\n".
                    "**********\n". 
                    "Name: ".strtoupper(Session::get('fullName'))."\n".
                    "Phone Number: ".Session::get('phoneNumber')."\n".
                    "Address: ".strtoupper(Session::get('address'))."\n".
                    "**********\n".
                    "Depot: ".strtoupper(Session::get('depotSelected')->name)."\n".
                    "Gas Tank: ".Session::get('productSelected')->name."\n".
                    "Quantity: ".Session::get('stockSelected')." tanks\n".
                    "Tax: Tsh".$tax_amount."/=\n".
                    "Total Price: Tsh".(Session::get('stockSelected') * Session::get('productSelected')->price) + $tax_amount."/=\n".
                    "**********\n".
                    "Please select:\n".
                    "1: Confirm your Order\n".
                    "0: Cancel your Order\n".
                    "#: Go back to the Main Menu";
        } else{
            Session::put('eighthMessageFlag', false);
            Session::put('seventhMessageFlag', true);
            return $this->handleSeventhMessage($userMessage);
        }
    }

    private function handleNinthMessage($userMessage)
    {
        if($userMessage == '#'){
            Session::flush();
            return "All sessions cleared. You are now back to the main menu. Say Hello";
        } else if ($userMessage == '1'){
            try {
                $order_id = 100000 + Order::all()->count() + 1;
                
                if (Order::find($order_id)) {
                    $order_id = Order::orderBy('id', 'DESC')->first()->id + 1;
                }
            
                $order = new Order();
                $order->id = $order_id;
            
                $order->user_id = 1111;
                $order->coupon_discount_title = null;
                $order->payment_status = 'paid';
                $order->order_status = 'confirmed';
                $order->order_type = 'delivery';
                $order->coupon_code = null;
                $order->payment_method = 'cash_on_delivery';
                $order->transaction_reference = null;
                $order->delivery_charge = 0; //since pos, no distance, no d. charge
                $order->delivery_address_id = null;
                $order->order_note = "Name: ".strtoupper(Session::get('fullName'))."\n".
                                     "Phone Number: ".Session::get('phoneNumber')."\n".
                                     "Address: ".strtoupper(Session::get('address'));
                $order->checked = 1;
                $order->created_at = now();
                $order->updated_at = now();
                //$order->total_tax_amount = (Helpers::tax_calculate(Session::get('productSelected'), Session::get('productSelected')->price)) * Session::get('stockSelected');
            
                $user_id = $order->user_id; // Get the user_id from the $order object
                $main_branch_id = 1; // HQ branch_id
            
                // OrderDetails
                $order_details = [
                    'order_id' => $order_id,
                    'product_id' => Session::get('productSelected')->id,
                    'product_details' => Session::get('productSelected'),
                    'quantity' => Session::get('stockSelected'),
                    'price' => Session::get('productSelected')->price,
                    'tax_amount' => Helpers::tax_calculate(Session::get('productSelected'), Session::get('productSelected')->price), //Helpers::tax_calculate($product, $price),
                    'discount_on_product' => 0, //Helpers::discount_calculate($product, $price),
                    'discount_type' => 'discount_on_product',
                    'variant' => null, //json_encode($c['variant']),
                    'variation' => null, //json_encode($c['variations']),
                    'unit' => 'kg',
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $total_tax_amount = $order_details['tax_amount'] * Session::get('stockSelected');
                $total_product_main_price = (Session::get('stockSelected') * Session::get('productSelected')->price) + $total_tax_amount;
                $order->order_amount = $total_product_main_price;
                OrderDetail::insert($order_details);
                $order->save();
            
                $sold_quantity = Session::get('stockSelected');
                $hq_inventory = Inventory::where('product_id', Session::get('productSelected')->id)
                                         ->where('branch_id', $main_branch_id) // HQ branch_id
                                         ->first();
                $inventory = Inventory::where('product_id', Session::get('productSelected')->id)
                                      ->where('branch_id', Session::get('depotSelected')->id)
                                      ->first();
            
                if ($inventory) {
                    $inventory->stocks -= $sold_quantity;
                    Session::get('productSelected')->total_stock -= $sold_quantity;
                    $inventory->save();
                    Session::get('productSelected')->save();
                } else {
                    Inventory::create([
                        'product_id' => Session::get('productSelected')->id,
                        'branch_id' => Session::get('depotSelected')->id,
                        'stocks' => $sold_quantity
                    ]);
                }

                // Session::forget('firstMessageFlag');
                // Session::forget('secondMessageFlag');
                // Session::forget('thirdMessageFlag');
                // Session::forget('fourthMessageFlag');
                // Session::forget('fifthMessageFlag');
                // Session::forget('sixthMessageFlag');
                // Session::forget('seventhMessageFlag');
                // Session::forget('eighthMessageFlag');
                // Session::forget('ninthMessageFlag');
                Session::flush();
                return "Order ID: ".$order->id." Placed Successfull. Say Hello to Access the MENU.";
            } catch (\Exception $e) {
                Session::flush();
                echo $e->getMessage();
                return "Order couldn't be placed. Say Hello";
            }
        } else{
            Session::flush();
            return "Order Cancelled. Say Hi or Hello to Access the Menu.";
        }
    }

    private function handleTrackingMessage($userMessage)
    {
        try{
            Session::put('orderTracked', Order::find(intval($userMessage)));
            if (Session::has('orderTracked')) {
                $orderStatus = Session::get('orderTracked')->order_status;
                Session::flush(); // Flush the session after successful tracking
                return "Order Status: " . strtoupper($orderStatus) . "\nSay Hi or Hello to Access the Menu.";
            } else {
                return "Order not found. Say Hi or Hello to Access the Menu.";
            }
            return "OKay";
        } catch(\Exception $e){
            Session::flush();
            return "An error occurred. Say Hi or Hello to Access the Menu.";
        }
    }

    private function sendMessage($message)
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $twilio = new Client($sid, $token);

        return $twilio->messages->create("whatsapp:+255683144573", [
            "from" => "whatsapp:+14155238886",
            "body" => $message,
        ]);
    }
}
