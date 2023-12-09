<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class CodeRewardVouchers
{
    public function RewardCode($data)
    {
          $queryData = [];
          $rejected = [];
          $succesed = 0;
          $rewards = [];
          $rejected_count = 0;
          $date = date('Y-m-d h:i:s');
          
                $rows = array_chunk($data, 2000);
                foreach ($rows as $key => $chukData1) {
                    foreach ($chukData1 as $key => $chukData) {
                        $reject = [];
                        if (!isset($chukData["Voucher Code"]) || empty($chukData["Voucher Code"])) {
                            $reject = [
                                "unique_code" => $chukData["Voucher Code"],
                                "reason" => "Voucher Code can not  be blenk.",
                            ];
                        }
                         else {
                            $brand = strtolower($chukData["Brand"]) == 'uber' ? 1 : 2;
                            $type = strtolower($chukData["Amount"]) == '75' ? 1 : 2;


                            $dataCheck = "('" . $chukData["Voucher Code"] . "', " . $chukData['Amount'] . ", " . $chukData['Batch'] . "," . $brand.",".$type.",1,0,'".$date."','".$date."')";
                            $insert = "INSERT INTO unique_code_reward_vouchers (voucher_code,amount,batch,brand_type, voucher_type,status,is_used,created_at,updated_at)
                            VALUES $dataCheck On CONFLICT(voucher_code) DO NOTHING;";
                            $insertedCount =  Yii::$app->db->createCommand($insert)->execute();
                            if ($insertedCount) {
                                $succesed += 1;
                            } else {
                                $reject = [
                                    "unique_code" => $chukData["Voucher Code"],
                                    "reason" => "Duplicate Voucher Code found in the file!!",
                                ];
                            }
                        }
                        if (!empty($reject)) {
                            $rejected[] = $reject;
                            $rejected_count +=1;
                        }
                    }
                }
            
        $insertedCount = 0;
        $res  = [
            "message" => "Success",
            "total_count" => $succesed+count($rejected),
            "uploaded_count" => $succesed,
            "rejected_count" => $rejected_count,
            "rejected" => $rejected,
        ];
        $res['msg'] = $succesed > 0 ? "Codes successfully uploaded." : "No data uploaded";
        return $res;
    
    }

}
