<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class VoucherCode
{
    public function VoucherCode($data)
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
                        if (!isset($chukData["Code"]) || empty($chukData["Code"])) {
                            $reject = [
                                "unique_code" => $chukData["Code"],
                                "reason" => "Code can not  be blenk.",
                            ];
                        }
                         else {
                            $type = $chukData["Point"] == '40' ? 1 : 2;
                            $dataCheck = "('" . $chukData["Code"] . "', " . $chukData['Point'] . ", " . $type . ",". $chukData["Fifa Ticket"].",0,0,'".$date."','".$date."')";
                            $insert = "INSERT INTO unique_codes (unique_code,points, voucher_type,fifa_tickets,status,is_used,created_date,updated_date)
                            VALUES $dataCheck On CONFLICT(unique_code) DO NOTHING;";
                            // print_r($insert);die;
                            $insertedCount =  Yii::$app->db->createCommand($insert)->execute();
                            if ($insertedCount) {
                                $succesed += 1;
                            } else {
                                $reject = [
                                    "unique_code" => $chukData["Code"],
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
