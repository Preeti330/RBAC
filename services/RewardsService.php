<?php

namespace app\services;

use Yii;
use app\models\Order;
use yii\web\HttpException;
use app\models\CustomerPointsSummary;
use app\models\OrderDetail;
use app\models\Product;

class RewardsService
{
    public function importRewards($data)
    {

        $rewardTitles = array_keys($data);
        $rewardexplode = "'" . implode("', '", $rewardTitles) . "'";
        $rewardTitleTrim = strtolower(trim($rewardexplode));
        $sql = "SELECT id,lower(brand_name) as brand_name FROM game_rewards where brand_name IS NOT NULL";
        $gameRewards = Yii::$app->db->createCommand($sql)->queryAll();
        $queryData = [];
        $rejected = [];
        $succesed = 0;
        if ($gameRewards != null  || count($gameRewards) > 0) {
            $rewards = [];
            foreach ($gameRewards as $key => $value) {
                $rewards[$value['brand_name']] = $value['id'];
            }
            foreach ($data as $gkey => $value) {
                $rows = array_chunk($value, 1000);
                foreach ($rows as $key => $chukData1) {
                    foreach ($chukData1 as $key => $chukData) {
                        $reject = [];
                        $rewardTitleTrim = strtolower(trim($chukData["Brand name"]));
                        if (!isset($chukData["Code"]) || empty($chukData["Code"])) {
                            $reject = [
                                "code" => $chukData["Code"],
                                "reason" => "Code can not  be blenk.",
                                "brand_name" => $chukData["Brand name"],
                            ];
                        } elseif (!isset($rewards[$rewardTitleTrim]) || empty($rewards[$rewardTitleTrim])) {
                            $reject = [
                                "code" => $chukData["Code"],
                                "reason" => "Brand not found  with given name.",
                                "brand_name" => $chukData["Brand name"],
                            ];
                        } elseif (!in_array($chukData["Type"], ["Unique", "Generic"])) {
                            $reject = [
                                "code" => $chukData["Code"],
                                "reason" => "Invalid type given.",
                                "brand_name" => $chukData["Brand name"],
                            ];
                        } else {
                            $type = $chukData["Type"] == 'Unique' ? 1 : 2;
                            $dataCheck = "('" . $chukData["Code"] . "', " . $rewards[$rewardTitleTrim] . ", " . $type . ",0,0)";
                            $insert = "INSERT INTO game_reward_vouchers (voucher_code, game_reward_id, voucher_type, status,is_used)
                    VALUES $dataCheck On CONFLICT(voucher_code) DO NOTHING;";
                            $insertedCount =  Yii::$app->db->createCommand($insert)->execute();
                            if ($insertedCount) {
                                $succesed += 1;
                            } else {
                                $reject = [
                                    "code" => $chukData["Code"],
                                    "reason" => "Code already exist.",
                                    "brand_name" => $chukData["Brand name"],
                                ];
                            }
                        }
                        if (!empty($reject)) {
                            $rejected[] = $reject;
                        }
                    }
                }
            }
            
        $insertedCount = 0;
        $res  = [
            "imported" => $succesed+count($rejected),
            "uploaded" => $succesed,
            "rejected" => $rejected,
        ];
        $res['msg'] = $succesed > 0 ? "Codes successfully uploaded." : "No data uploaded";
        return $res;
    }else{
        throw new HttpException(466, json_encode("Reward not found."));
    }
}

    public function transactionList($mobile_no='')
    {
        $mobileFIlter = '';
        if(isset($mobile_no) && !empty($mobile_no)){
            $mobileFIlter = " AND customers.mobile='$mobile_no' ";
        }
        $sql = "SELECT UserPoint.description,
        UserPoint.points,
        customers.customer_name,
        customers.mobile,
        customer_points_summary.total_points,
        TO_CHAR(UserPoint.created_date,'Mon DD, YYYY') created_on,
        CASE WHEN UserPoint.points_type = 1 THEN 'Code Upload'
        WHEN UserPoint.points_type = 2 THEN 'Referral'
        WHEN UserPoint.points_type = 3 THEN 'Invoice Upload'
        WHEN UserPoint.points_type = 4 THEN 'Spin The Wheel'
        WHEN UserPoint.points_type = 5 THEN 'Profile Completion'
        WHEN UserPoint.points_type = 6 THEN 'Checkin'
        ELSE NULL END module,
        CASE WHEN UserPoint.points_mehtod = 1 THEN 'Credit'
        WHEN UserPoint.points_mehtod = 2 THEN 'Debit'
        ELSE NULL END AS type
        FROM customer_points_ledger AS UserPoint
        INNER JOIN customers on customers.id = UserPoint.customer_id
        LEFT JOIN customer_points_summary on customers.id = customer_points_summary.customer_id
        WHERE UserPoint.points_type IS NOT NULL
        $mobileFIlter
        ORDER BY UserPoint.created_date DESC";
		$data = Yii::$app->db->createCommand($sql)->queryAll();
		return $data;
    }

    public function OrderOfferCodeUpload($orders)
    {
            $date 	   = date('Y-m-d h:i:s');
			$orderdetailsid = [];
			$orderids = [];
			$program_id = Yii::$app->request->get('program_id');
            $connection  =  \Yii::$app->db;
			$transaction = $connection->beginTransaction();
			try{
				$updated =0;
				$rejected_orders 	=	[];
				$template_details   = array();
                if(isset($orders[1]) && !empty($orders[1])){
                    $finalData = $orders[0];
                }else{
                    $finalData = $orders;
                }
				foreach($finalData as $order){
                   
                    $evoucher_code = $expiry_date = $expiry_date_formatted = $evoucher_pin = $tc_url = '';
                    if(isset($order['egift_voucher']) && !empty($order['egift_voucher'])){
					    $evoucher_code = $order['egift_voucher'];
                    }
                    if(isset($order['Expiry Date']) && !empty($order['Expiry Date'])){
                        $expiry_date = $order['Expiry Date'];
						$expiry_date_formatted = date('Y-m-d', strtotime($expiry_date));
                    }
                    if(isset($order['Activation Link']) && !empty($order['Activation Link'])){
                        $tc_url = $order['Activation Link'];

                    }
                    if(isset($order['pin']) && !empty($order['pin'])){
                        $evoucher_pin = $order['pin'];
                    }

						$order_id = $order['Order Id'];
						$order_detail_id = $order['Order Detail Id'];
						$offer_order_id = $order['Offer Code id'];
						$product_id = $order['Product Id'];
						$phone_number = $order['Mobile'];
						$product_name = $order['Product Name'];
						$user_id      = $order['Customer Id'];
                        $reason['reason'] = '';
                        if(trim($order['Order Status']) == "Failed"){
                            
                            $reward_orderFailed = Order::find()->where(['id'=>$order_id ,'user_id'=>$user_id])->one();
                            $rewardOrderDetailFailed = OrderDetail::find()->where(['id'=>$order_detail_id])->one();
                            $order_offer_codeFailed = "SELECT * FROM orderoffercodes WHERE id=".$offer_order_id."";
                            $offer_codeFailed            = Yii::$app->db->createCommand($order_offer_codeFailed)->queryOne();
                            // print_r($offer_codeFailed); exit;
                            if($offer_codeFailed['order_internal_status']!=6){
                                if(isset($order['Cancelling Reason']) && !empty($order['Cancelling Reason'])){
                                    $cancellingReason      = $order['Cancelling Reason'];
                                    $cancelling_reason = "SELECT * FROM order_cancelling_reason WHERE reason = ".$cancellingReason;
                                    $reason            = Yii::$app->db->createCommand($cancelling_reason)->queryOne();
                                    $reward_orderFailed->cancelling_reason_id = $reason['id'];
                                }   
                                $reward_orderFailed->save();
                                $ustomer_point_summaryFailed  = CustomerPointsSummary::find(['customer_id'=>$user_id])->one();
                                $user_bal_pointFailed     = $ustomer_point_summaryFailed['total_points'] + $reward_orderFailed['total_points'];
                                $ustomer_point_summaryFailed->save();
                                $reasonString = '';
                                if(!empty($reason['reason'])){
                                    $reasonString = ". Reason: ".$reason['reason'];
                                }
                                $product = Product::find()->where(['id'=>$offer_codeFailed ['reward_product_id']])->one();
                                // if($rewardOrderDetailFailed['qty'] > 1){
                                //     $ProdPoints = $reward_orderFailed->total_points/$rewardOrderDetailFailed['qty'];
                                //     for ($i=0; $i <2; $i++) { 
                                //         $descriptionFailed        = "Points credited for order cancelled. Order ID:OD".$reward_orderFailed['id'].$reasonString;
                                //         $sql_catalouge_trans    = "insert into customer_points_ledger(customer_id, description, points, 
                                //         created_date, points_mehtod, points_type, balance_points,status,points_type_id)  values (".$reward_orderFailed['user_id'].",'".$descriptionFailed."', ".$ProdPoints.",'".$date."',1,3,".$user_bal_pointFailed.",1,".$reward_orderFailed->id.")";
                                //         $points_ledger = Yii::$app->db->createCommand($sql_catalouge_trans)->execute();
                                //      }
                                // }else{
                                    $descriptionFailed        = "Points credited for order cancelled. Order ID:OD".$reward_orderFailed['id'].$reasonString;
                                    $sql_catalouge_trans    = "insert into customer_points_ledger(customer_id, description, points, 
                                    created_date, points_mehtod, points_type, balance_points,status,points_type_id)  values (".$reward_orderFailed['user_id'].",'".$descriptionFailed."', ".$product['points'].",'".$date."',1,3,".$user_bal_pointFailed.",1,".$reward_orderFailed->id.")";
                                    $points_ledger = Yii::$app->db->createCommand($sql_catalouge_trans)->execute();
                                    
                                // }
                                
                                array_push($orderdetailsid, $order_detail_id);
                                array_push($orderids, $order_id);
                                $temp_orderids = implode(",", array_unique($orderids));
                                $update_orderstatusFailed = "UPDATE orders SET order_internal_status=6, updated_date='" . $date . "'  WHERE id IN ( " . $temp_orderids . ")";
                                //$query1 = Yii::$app->db->createCommand($update_orderstatus)->execute();
                                Yii::$app->db->createCommand($update_orderstatusFailed)->execute();

                                $temp_orderdetailidsfailed = implode(",", array_unique($orderdetailsid));

                                $update_orderdetailstatusFailed = "UPDATE orderdetails SET order_internal_status=6, updated_date='" . $date . "', delivered_on='" . $date . "' , cancelled_on='" . $date . "'  WHERE id IN ( " . $temp_orderdetailidsfailed . ")";
                                //$query2 = Yii::$app->db->createCommand($update_orderdetailstatus)->execute();
                                Yii::$app->db->createCommand($update_orderdetailstatusFailed)->execute();
                                $update_offercodeFailed = "UPDATE orderoffercodes SET order_internal_status=6   WHERE id = " . $offer_order_id . " AND order_id = " . $order_id . " AND order_detail_id = " . $order_detail_id;
                                Yii::$app->db->createCommand($update_offercodeFailed)->execute();
                                $updated++;	
                            }
                            // else{
                            //     $rej_reason['order_id'] 	= $order_id;
                            //     $rej_reason['phone_number'] = $phone_number;
                            //     $rej_reason['product_name'] = $product_name;
                            //     $rej_reason['reason']   	= "Order Already Cancelled";
                            // }		
                        }elseif(trim($order['Order Status']) == "Approved"){
						$sql_duplicate_voucher = "select * from orderoffercodes WHERE offer_code='" . $evoucher_code . "' and reward_product_id =" . $product_id;
						$sqldata = Yii::$app->db->createCommand($sql_duplicate_voucher)->queryone();
						if (empty($sqldata)) {
						//checking same vouchercode already there for same product
							$sql_offercode_check = "select * from orderdetails left join orderoffercodes on
							 orderoffercodes.order_detail_id = orderdetails.id  WHERE orderoffercodes.id=" .$offer_order_id;
                            //  echo $sql_offercode_check;die;
							$offercode_data = Yii::$app->db->createCommand($sql_offercode_check)->queryone();
							if (empty($offercode_data['offer_code'])) {
									// if(!empty($expiry_date && $tc_url)){
										if(empty($tc_url) || filter_var($tc_url, FILTER_VALIDATE_URL) !== false) {
							            array_push($orderdetailsid, $order_detail_id);
										array_push($orderids, $order_id);
                                        
					                    if(!empty($evoucher_code)){
                                            
										// $update_offercode = "UPDATE orderoffercodes SET offer_code='" . $evoucher_code . "', offer_code_sent_on = '" . $date . "', offercode_pin='" . $evoucher_pin . "',  updated_date='" . $date . "'  WHERE id = " . $offer_order_id . " AND order_id = " . $order_id . " AND order_detail_id = " . $order_detail_id;
                                        $update_offercode = "UPDATE orderoffercodes SET offer_code='" . $evoucher_code . "',order_internal_status=4, offer_code_sent_on = '" . $date . "', offercode_pin='" . $evoucher_pin . "', updated_date='" . $date . "', offercode_validity='" . $expiry_date . "', activation_url='" . $tc_url . "'  WHERE id = " . $offer_order_id . " AND order_id = " . $order_id . " AND order_detail_id = " . $order_detail_id;

										//$query = Yii::$app->db->createCommand($update_offercode)->execute();
                                        }else{
                                            $update_offercode = "UPDATE orderoffercodes SET order_internal_status=4   WHERE id = " . $offer_order_id . " AND order_id = " . $order_id . " AND order_detail_id = " . $order_detail_id;

                                        }
                                        Yii::$app->db->createCommand($update_offercode)->execute();
										$temp_orderids = implode(",", array_unique($orderids));
										$update_orderstatus = "UPDATE orders SET order_internal_status=4, updated_date='" . $date . "'  WHERE id IN ( " . $temp_orderids . ")";
										//$query1 = Yii::$app->db->createCommand($update_orderstatus)->execute();
                                        Yii::$app->db->createCommand($update_orderstatus)->execute();
										$temp_orderdetailids = implode(",", array_unique($orderdetailsid));
                                        
										$update_orderdetailstatus = "UPDATE orderdetails SET order_internal_status=4, updated_date='" . $date . "', delivered_on='" . $date . "'  WHERE id IN ( " . $temp_orderdetailids . ")";
										//$query2 = Yii::$app->db->createCommand($update_orderdetailstatus)->execute();
                                        Yii::$app->db->createCommand($update_orderdetailstatus)->execute();

										$updated++;
										$success_saving_all = true;

										$reward_order = Order::find()->where(['id'=>$order_id ,'user_id'=>$user_id])->one();
											
										##Push Notification----------------
										// if(empty($template_details)){
					                    //     $push_notify = new CustomerNotification();
					                    //     $template_details = $push_notify->getTemplateNotificationDetails($program_id, 'Order Processed');                        
					                    // }
					                    // if(!empty($template_details)){
					                    //     $values_array = array();
					                    //     $values_array['prod_name']        = $product_name;
					                    //     $values_array['order_id']         = $order_id;
					                    //     $values_array['reason']           = '';
					                    //     $values_array['delivery_partner'] = (isset($reward_order['delivery_partner']) ? $reward_order['delivery_partner'] : '');
					                    //     $values_array['way_bill']         = (isset($reward_order['way_bill_number']) ? $reward_order['way_bill_number'] : '');
					                    //     $push_notify = new CustomerNotification();
					                    //     $push_notify->PushNotificationProcess($program_id, 'Order Processed', $template_details, $user_id, $values_array); 
					                    // }
					                    ##Push Notification------------------        
					                    } else {
										$rej_reason['order_id'] 	= $order_id;
										$rej_reason['phone_number'] = $phone_number;
										$rej_reason['product_name'] 	= $product_name;
										$rej_reason['reason']   	= "Terms and Condition URL is Invalid.";				
												}
									// } else {
									// $rej_reason['order_id'] 	= $order_id;
									// $rej_reason['phone_number'] = $phone_number;
									// $rej_reason['product_name'] 	= $product_name;
									// $rej_reason['reason']   	= "Empty Expiry Date or Terms and Condition Link.";	
									// }
								} else {
							$rej_reason['order_id'] 	= $order_id;
							$rej_reason['phone_number'] = $phone_number;
							$rej_reason['product_name'] 	= $product_name;
							$rej_reason['reason']   	= 'Same order has been delivered already on ' . $offercode_data['offer_code_sent_on'];		
								}
						} else {
						    $rej_reason['order_id'] 	= $order_id;
							$rej_reason['phone_number'] = $phone_number;
							$rej_reason['product_name'] 	= $product_name;
							$rej_reason['reason']   	= "Duplicate Voucher code found in the file!!";
						}
					}
                    //  else {
					// 	    $rej_reason['order_id'] 	= $order_id;
					// 		$rej_reason['phone_number'] = $phone_number;
					// 		$rej_reason['product_name'] 	= $product_name;
					// 		$rej_reason['reason']   	= "No status change";
					// }
                    
					if(!empty($rej_reason)){
						$rejected_orders[] = $rej_reason;
					}
                
				}
				
				$transaction->commit();
			}catch (Exception $e) {
				$transaction->rollback();
				$success_saving_all = false;
			}
        $response['message'] 		= 'Data imported successfully.';
        $response['import_count'] 	= count($finalData);
        $response['uploaded_count'] = $updated;
        $response['rejected_orders']= $rejected_orders;
        return $response;
    }
    public function getRewardVouchersCount()
    {
        $sql = "SELECT gr.brand_name,count(grv.id) as total_count FROM game_rewards gr inner join game_reward_vouchers grv on gr.id=grv.game_reward_id
         group by gr.brand_name";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        if(empty($data)){
            throw new HttpException(466, json_encode("No Reward brands and vouchers found."));
        }
		return $data;
    }
}
