<?php

namespace App\CentralLogics;

use Faker\Extension\Helper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessSetting; 


class Helpers
{
    public static function error_processor($validator)
    {
        $err_keeper = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            array_push($err_keeper, ['code' => $index, 'message' => $error[0]]);
        }
        return $err_keeper;
    }

    public static function get_business_settings($name)
    {
        $config = null;

        $paymentmethod = BusinessSetting::where('key', $name)->first();

        if ($paymentmethod) {
           
            $config = json_decode(json_encode($paymentmethod->value), true);
            $config = json_decode($config, true);
        }

        return $config;
    } 

    public static function currency_code(){
        return BusinessSetting::where(['key' => 'currency'])->first()->value;
    }

    public static function send_order_notification($order, $token){
        try {
        $status = $order->order_status;
        $value = self::order_status_update_message($status);

        if($value){
            $data = [
                'title' =>trans('message.order_push_title'),
                'description' =>$value,
                'order_id'=> $order->id,
                'image' =>'',
                'type'=>'order_status',
            ];

            self::send_push_notif_to_device($token, $data);

            try{
                DB::table('user_notifications')-> insert([
                    'data'=> json_encode($data),
                    'user_id'=> $order ->user_id,
                    'created_at'=>now(),
                    'updated_at'=>now()
                ]);
            }catch(\Exception $e){
                return respose()->json([$e], 403);
            }

        }
            return true;
        }catch(\Exception $e){
            info($e);
        }
        return false;
    }

    public static function order_status_update_message($status){
        if($status == 'spending'){
            $data = BusinessSetting::where('key', 'order_pending_message')->first();
        }elseif ($status == 'confirmed'){ 
            $data = BusinessSetting::where('key', 'order_confirmation_msg')->first();
        }elseif ($status == 'processing'){
            $data = BusinessSetting::where('key', 'order_processing_message')->first();
        }elseif ($status == 'picked_up'){
            $data = BusinessSetting::where('key', 'our_for_delivery_message')->first();
        }elseif ($status == 'handover'){
            $data = BusinessSetting::where('key', 'order_handover_message')->first();
        }elseif ($status == 'delivered'){
            $data = BusinessSetting::where('key', 'order_delivered_message')->first();
        }elseif ($status == 'delivery_boy_delivered'){
            $data = BusinessSetting::where('key', 'delivery_boy_delivered_message')->first();
        }elseif ($status == 'accepted'){
            $data = BusinessSetting::where('key', 'delivery_boy_assign_message')->first();
        }elseif ($status == 'canceled'){
            $data = BusinessSetting::where('key', 'order_canceled_message')->first();
        }elseif ($status == 'refunded'){
            $data = BusinessSetting::where('key', 'order_refunded_message')->first();
        }else {
            $data = '{"status":"0", "message":""}';
        }

        return $data['value']['message'];
    }

}