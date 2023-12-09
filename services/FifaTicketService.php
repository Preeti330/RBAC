<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class FifaTicketService
{
    public function FifaTicketService($data)
    {
          $queryData = [];
          $rejected = [];
          $succesed = 0;
          $rewards = [];
          $rejected_count = 0;
          $date = date('Y-m-d h:i:s');
            $insertedData = [];
                $rows = array_chunk($data, 2000);
                foreach ($rows as $key => $chukData1) {
                    foreach ($chukData1 as $key => $chukData) {
                         if (!isset($chukData["Reference Id"]) || empty($chukData["Reference Id"])) {
                            $reject = [
                                "code" => $chukData["Reference Id"],
                                "reason" => "Code can not  be blenk.",
                             ];
                         }   
                            $dataCheck = "('" . $chukData["Reference Id"] . "', '".uniqid(). "',0,0,'".$date."','".$date."')";
                            array_push($insertedData,$dataCheck); 
                        }
                    }
           $List = implode(', ', $insertedData);
            $insert = "INSERT INTO fifa_tickets (unique_code,ticket,status,is_used,created_at,updated_at)
                    VALUES $List On CONFLICT(unique_code) DO NOTHING;";
                $insertedCount =  Yii::$app->db->createCommand($insert)->execute();
                if ($insertedCount) {
                     $succesed = 1;
                    }      
         $res  = [
            "message" => "Success"
         ];
        $res['msg'] = $succesed > 0 ? "Fifa Codes successfully uploaded." : "No data uploaded";
        return $res;
    }

}
