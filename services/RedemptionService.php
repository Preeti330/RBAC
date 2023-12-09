<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class RedemptionService
{
    public function physicalOrdersHeader()
    {
        $columns   = [
            'customer_id',
            'customer_name',
            'contact_no',
            'email',
            'order_date',
            'order_id',
            'prod_name',
            'address',
            'sku','order_internal_status',
            'cancelling_reason',
            'delivery_partner',
            'order_tracking_id'
        ];
        $headers   = [
            "customer_id" => "Customer Id",
            "customer_name" => "Name",
            "contact_no" => "Mobile",
            "email" => "Email",
            'order_date' => 'Ordered Date',
            'order_id' => 'Order ID',
            'prod_name'=>'Product Name',
            'address'=>'Address',
            'sku'=>'SKU',
            'order_internal_status'=>'Status',
            'cancelling_reason'=>'Cancelling Reason',
            'delivery_partner'=>'Delivery Partner',
            'order_tracking_id'=>'Way Bill Number'
        ];
        return [
            "columns" => $columns,
            "headers" => $headers,
        ];
    }

    public function eVoucherOrderHeader()
    {
        $columns = [
            'customer_id',
            'customer_name',
            'contact_no',
            'email',
            'order_id',
            'orderdetail_id',
            'prod_id',
            'prod_name',
            'offercode_id',
            'order_status',
            'points_burnt',
            'qty',
            'denomiation',
            'order_placed_date',
            'offer_code',
            'offercode_pin',
            'offercode_validity',
            'activation_url',
            'cancelling_reason',
    ]; //without header working, because the header will be get label from attribute label. 
        $headers = [
            "customer_id" => "Customer Id",
            "customer_name" => "Name",
            "contact_no" => "Mobile",
            "email" => "Email",
            "order_id" => "Order Id",
            "orderdetail_id" => "Order Detail Id",
            "prod_id" => "Product Id",
            "prod_name" => "Product Name",
            "offercode_id" => "Offer Code id",
            "order_status" => "Order Status",
            "points_burnt" => "Points Burnt",
            "qty" => "Quantity",
            "denomiation" => "Denominations",
            "order_placed_date" => "Order Placed Date",
            "offer_code" => "egift_voucher",
            "offercode_pin" => "pin",
            'cancelling_reason'=>'Cancelling Reason',
            "offercode_validity" => "Expiry Date",
            "activation_url" => "Activation Link",
        ];
        return [
            "columns" => $columns,
            "headers" => $headers,
        ];
    }
}