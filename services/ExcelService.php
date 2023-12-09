<?php

namespace app\services;

use Yii;
use yii\web\HttpException;

class ExcelService
{
    public function UploadExcel($dir,$setFirstRecordAsKeys=true,$setIndexSheetByName=true)
    {
     $uploads = \yii\web\UploadedFile::getInstancesByName("excel_data");
     if (empty($uploads)) {
         throw new HttpException(422, json_encode('Please upload atleast one file.'));
     }
     
     $path = 'uploads/'.$dir.'/' . $uploads[0]->name;
     $dirctory = 'uploads/'.$dir.'/';
     $ext = pathinfo($path, PATHINFO_EXTENSION);
     $pathToSave = 'uploads/'.$dir.'/'.date('Y-m-d').'-'.uniqid() .'.'. $ext;
     if (!file_exists($dirctory)) {
        mkdir($dirctory, 0777, true);
    }
     if ($ext !== 'xlsx') {
         throw new HttpException(422, json_encode('Error!!! Please check the file type'));
     }
     $uploads[0]->saveAs($pathToSave); //Uploaded file is saved.
     $data = \moonland\phpexcel\Excel::widget([
         'mode' => 'import',
         'fileName' => $pathToSave,
         'setFirstRecordAsKeys' => $setFirstRecordAsKeys, // if you want to set the keys of record column with first record, if it not set, the header with use the alphabet column on excel.
         'setIndexSheetByName' => $setIndexSheetByName, // set this if your excel data with multiple worksheet, the index of array will be set with the sheet name. If this not set, the index will use numeric.
         // 'getOnlySheet' => 'sheet2', // you can set this property if you want to get the specified sheet from the excel data with multiple worksheet.
     ]);
     return $data;
    }

    public function Export($dir,$data,$headers,$column)
    {
        
        $fileName =  date('Y-m-d H-i-s').'-'.uniqid(). ".xlsx";
        $dirctory = 'uploads/'.$dir.'/';
        // $filetosave = $dirctory.'/' . $file_name;
        if (!file_exists($dirctory)) {
            mkdir($dirctory, 0777, true);
        }

        \moonland\phpexcel\Excel::export([
            'models' => $data, 
            'columns' => $column,
            'headers' => $headers,
            'fileName' => $fileName,//File name
            'savePath' =>$dirctory,//File should be saved here
        ]);
        $response['message'] = "Successfully downloaded your data.";
        $response['excel']    = \yii\helpers\Url::home(true).$dirctory.$fileName;
        return $response;
    }
}