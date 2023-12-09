<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\base\ErrorException;

use Exception;

use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\auth\CompositeAuth;
use app\models\LoginForm;
use app\models\User;
use app\models\Kpis;
use app\models\WallOfFame;

use yii\web\UploadedFile;
use yii\web\HttpException;
use app\filters\auth\HttpBearerAuth;
use app\helpers\AppHelper;
use app\models\Brands;
use app\models\outlet;
use app\models\Outlet as ModelsOutlet;
use app\models\OutletUsersMapping;
use app\models\Region;
use app\models\Registration;
use app\models\TargetBrandWise;
use app\models\TargetConfig;
use app\models\UserOutlet;
use app\models\BrandSkus;
use app\models\DayWiseSkuSalesTrans;
use app\models\HnkVisibilityTransCumulative;
use app\models\MilestoneSlabs;
use app\models\Vouchers;
use app\models\VoucherCodes;
use app\models\UsersWining;
use app\models\Faqs;
use app\models\GeneralSettings;
use app\models\State;
use app\models\City;
use moonland\phpexcel\Excel;
use yii\web\ServerErrorHttpException;
use app\models\SalesTransCumulativeBrandWise;

class AdminController extends ActiveController
{

    public $modelClass = 'app\models\User';
    // public $modelClass1 = 'app\models\User';
    public function actions()
    {
        return [];
    }

    public function behaviors()
    {
        
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],

        ];

        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::className(),
            'actions' => [
                'registration' => ['post'],
                'login' => ['post'],
                'rbac'=>['post'],
                'displaysum'=>['get']
            ]
        ];
        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Allow-Origin' => ['*'],
            ],
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = [
            'options',
            'registration',
            'login',

        ];

        // setup access
        
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => [
                        'index', 'create', 'view', 'update', 'delete',
                        'displaysum',
                        'rbac'
                    ], //only be applied to

                    
            'rules' => [
                [
                    'allow'=>true,
                    'actions'=>['displaysum','rbac'],
                    'roles'=>['hubadmin']
                ],
            ],
        
        ];
        return $behaviors;
    }

    public function actionOptions($id = null)
    {
        return 'ok';
    }

    public function getBearerAccessToken()
    {
        $bearer = null;
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $matches = array();
            preg_match('/^Bearer\s+(.*?)$/', $headers['Authorization'], $matches);
            if (isset($matches[1])) {
                $bearer = $matches[1];
            }
        } elseif (isset($headers['authorization'])) {
            $matches = array();
            preg_match('/^Bearer\s+(.*?)$/', $headers['authorization'], $matches);
            if (isset($matches[1])) {
                $bearer = $matches[1];
            }
        }
        return $bearer;
    }
   


    public function actionRegistration()
    {


        set_time_limit(0);
        $date = date('Y-m-d H:i:s');
        $current_date = date('Y_m_d_H_i_s');

        $apphelper = new AppHelper();

        $file = $_FILES['upload_file'];
        $instance = UploadedFile::getInstanceByName('upload_file');
        $import_data = $apphelper->getExcelData($file, $instance, 'registration_excel');

        $approved = 0;
        $rejected = 0;
        $approvedUser = [];
        $rejectedUser = [];

        $transaction = \Yii::$app->db->beginTransaction();
        

        try {

            foreach ($import_data as $key => $val) {


                $region_name = (!empty($val['Region']) && isset($val['Region']) && $val['Region'] != NULL) ? $val['Region'] : 'NULL';

                $region_id = Region::Getidbyname($region_name);
                $outletenrollment = new Outlet();

                $checkOutlet = $outletenrollment->getOutletDetailsOnOutletCode($val['outlet_code']);

                if (empty($checkOutlet) && $checkOutlet == NULL) {
                    $outletenrollment = new Outlet();
                    $outletenrollment->rocode         = (string) $val['outlet_code'];
                    $outletenrollment->roname         = (string) $val['outlet_name'];
                    $outletenrollment->region_id      = (int)$region_id;
                    $outletenrollment->channel_id     = 1;
                    $outletenrollment->depot_dbf      = (string) $val['Depot'];
                    // $outletenrollment->save();
                    if ($outletenrollment->save()) {

                        $outlet_id = Yii::$app->db->getLastInsertId();
                    } else {
                        return $outletenrollment->errors;
                    }
                } else {
                    $outlet_id = $checkOutlet['id'];
                }

                $outlet_user_mapping = new UserOutlet();
                $checkOutletMapping = UserOutlet::find()->where(['outlet_id' => $outlet_id])->one();

                if (empty($checkOutletMapping) && $checkOutletMapping == NULL) {

                    $Asm_Id = 0;
                    $Ase_Id = 0;
                    $Tse_Id = 0;
                    $Jtse_Id = 0;
                    $is_dummy = 0;

                    $registrationModel = new Registration();
                    $checkASM =
                        $registrationModel->checkUSER($val['ASM_Mobile_Number']);

                    $ase_number =
                        (!empty($val['ASE_Mobile_Number']) && isset($val['ASE_Mobile_Number']) && $val['ASE_Mobile_Number'] != NULL) ? $val['ASE_Mobile_Number'] : NULL;

                    $tse_number =
                        (!empty($val['TSE_Mobile_Number']) && isset($val['TSE_Mobile_Number']) && $val['TSE_Mobile_Number'] != NULL) ? $val['TSE_Mobile_Number'] : NULL;

                    $jtse_number =
                        (!empty($val['JTSE_Mobile_Number']) && isset($val['JTSE_Mobile_Number']) && $val['JTSE_Mobile_Number'] != NULL) ? $val['JTSE_Mobile_Number'] : NULL;


                    if (empty($checkASM) && $checkASM == NULL) {
                        // echo "asm : 1  ".$val['ASM_Mobile_Number']."<br>";
                        $asm_number = $val['ASM_Mobile_Number'];
                        $asmenrollment = new Registration();
                        $asmenrollment->username      = $val['ASM_Name'];
                        $asmenrollment->mobile_no     = "$asm_number";
                        $asmenrollment->user_role_id  = 4;
                        $asmenrollment->email_id    = $val['ASM_Email_Id'];
                        $asmenrollment->updated_date  = $date;
                        $asmenrollment->is_dummy  = 0;
                        // print_r($asm_number);exit;

                        if ($asmenrollment->save()) {
                            $Asm_Id = Yii::$app->db->getLastInsertId();

                            $aseenrollment = new Registration();
                            if ($ase_number == NULL) {
                                $username = "ASM_" . $Asm_Id;
                                $is_dummy = 1;
                                $checkASE = $registrationModel->checkRepeatedUser($username);
                                $aseenrollment->mobile_no     = NULL;
                                $aseenrollment->is_dummy = 1;
                            } else {
                                $username = $val['ASE_Name'];
                                $is_dummy = 0;
                                $checkASE = $registrationModel->checkUSER($ase_number);
                                $aseenrollment->mobile_no     = "$ase_number";
                                $aseenrollment->is_dummy = 0;
                            }

                            if (empty($checkASE) && $checkASE == NULL) {
                                $aseenrollment->username      = $username;
                                // $aseenrollment->mobile_no     = $ase_number;
                                $aseenrollment->user_role_id  = 5;
                                $aseenrollment->email_id     = $val['ASE_Email_Id'];
                                $aseenrollment->updated_date  = $date;

                                if ($aseenrollment->save(false)) {
                                    $Ase_Id = Yii::$app->db->getLastInsertId();


                                    // if (($tse_number == NULL)) {
                                    //     $username = "ASE_" . $Ase_Id;
                                    //     $is_dummy = 1;
                                    // } else {
                                    //     $username = $val['TSE_Name'];
                                    //     $is_dummy = 0;
                                    // }

                                    $tseenrollment = new Registration();
                                    if ($tse_number == NULL) {
                                        $username = "ASE_" . $Asm_Id;
                                        $is_dummy = 1;
                                        $checkTSE =    $registrationModel->checkRepeatedUser($username);
                                        $tseenrollment->mobile_no     = NULL;
                                        $tseenrollment->is_dummy = 1;
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $is_dummy = 0;
                                        $checkTSE = $registrationModel->checkUSER($tse_number);
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy = 0;
                                    }

                                    if (empty($checkTSE) && $checkTSE == NULL) {

                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id     = $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;

                                        if ($tseenrollment->save()) {
                                            $Tse_Id = Yii::$app->db->getLastInsertID();
                                            $jtseenrollment = new Registration();

                                            if ($jtse_number == NULL) {
                                                $username = "TSE_" . $Asm_Id;
                                                $is_dummy = 1;
                                                $checkJTSE =    $registrationModel->checkRepeatedUser($username);
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy = 1;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $is_dummy = 0;
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy = 0;
                                            }

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {

                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;

                                                $jtseenrollment->save();
                                                $Jtse_Id = Yii::$app->db->getLastInsertID();

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' => $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            } else {
                                                $Jtse_Id = $checkJTSE['id'];
                                                $rejected = $rejected + 1;

                                                $msgArray = [

                                                    'outlet_code' => $val['outlet_code'],
                                                    'message' =>
                                                    ' JTSE Already Registred Sucessffully.'
                                                ];
                                                array_push($rejectedUser, $msgArray);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            return $asmenrollment->errors;
                        }



                        $outlet_user_mapping->outlet_id    = $outlet_id;
                        $outlet_user_mapping->asm_id       = $Asm_Id;
                        $outlet_user_mapping->ase_id       = $Ase_Id;
                        $outlet_user_mapping->tse_id       = $Tse_Id;
                        $outlet_user_mapping->jtse_id      = $Jtse_Id;
                        $outlet_user_mapping->is_dummy = 0;
                        $outlet_user_mapping->updated_date = $date;
                        $outlet_user_mapping->save(false);
                    } else {
                        //asm is registered

                        $checkASM->updated_date  = $date;
                        $checkASM->is_dummy  = 0;

                        if ($checkASM->save()) {
                            $Asm_Id = $checkASM['id'];
                            $aseenrollment = new Registration();

                            if ($ase_number == NULL) {
                                $username = "ASM_" . $Asm_Id;
                                $is_dummy = 1;
                                $checkASE = $registrationModel->checkRepeatedUser($username);
                                $aseenrollment->mobile_no     = NULL;
                                $aseenrollment->is_dummy = 1;
                            } else {
                                $username = $val['ASE_Name'];
                                $is_dummy = 0;
                                $checkASE = $registrationModel->checkUSER($ase_number);
                                $aseenrollment->mobile_no     = "$ase_number";
                                $aseenrollment->is_dummy = 0;
                            }

                            if (empty($checkASE) && $checkASE == NULL) {

                                $aseenrollment->username      = $username;
                                // $aseenrollment->mobile_no     = $ase_number;
                                $aseenrollment->user_role_id  = 5;
                                $aseenrollment->email_id     = $val['ASE_Email_Id'];
                                $aseenrollment->updated_date  = $date;

                                if ($aseenrollment->save()) {
                                    $Ase_Id = Yii::$app->db->getLastInsertId();

                                    $tseenrollment = new Registration();
                                    if (($tse_number == NULL)) {
                                        $username = "ASE_" . $Ase_Id;
                                        $checkTSE = $registrationModel->checkRepeatedUser($username);
                                        $is_dummy = 1;
                                        $tseenrollment->mobile_no     = NULL;
                                        $tseenrollment->is_dummy = 1;
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $checkTSE =
                                            $registrationModel->checkUSER($tse_number);
                                        $is_dummy = 0;
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy = 0;
                                    }

                                    if (empty($checkTSE) && $checkTSE == NULL) {

                                        $tseenrollment = new Registration();
                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id     =
                                            $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;

                                        if ($tseenrollment->save()) {
                                            $Tse_Id = Yii::$app->db->getLastInsertId();
                                            $jtseenrollment = new Registration();
                                            if (($jtse_number == NULL)) {
                                                $username = "TSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy = 1;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy = 0;
                                            }


                                            if (empty($checkJTSE) && $checkJTSE == NULL) {

                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;

                                                $jtseenrollment->save();
                                                $Jtse_Id = Yii::$app->db->getLastInsertID();

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            } else {

                                                $checkJTSE->updated_date  = $date;

                                                $checkJTSE->save();
                                                $Jtse_Id = $checkJTSE['id'];

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            }
                                        }
                                    } else {
                                        //if tse registered 
                                        $checkTSE->updated_date  = $date;

                                        if ($checkTSE->save()) {
                                            $Tse_Id = $checkTSE['id'];
                                            $jtseenrollment = new Registration();

                                            if (($jtse_number == NULL)) {
                                                $username = "TSE_" . $Tse_Id;
                                                $checkJTSE =         $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy = 1;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy = 0;
                                            }

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;

                                                $jtseenrollment->save();
                                                $Jtse_Id = Yii::$app->db->getLastInsertID();

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            } else {


                                                $checkJTSE->updated_date  = $date;

                                                $checkJTSE->save();
                                                $Jtse_Id = $checkJTSE['id'];

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            }
                                        }
                                    }
                                }
                            } else {
                                // if ase is already registered
                                $checkASE->username = $username;
                                $checkASE->updated_date  = $date;
                                // $checkASE->is_dummy  = 0;

                                if ($checkASE->save()) {
                                    $Ase_Id = $checkASE['id'];

                                    $tseenrollment = new Registration();

                                    if (($tse_number == NULL)) {
                                        $username = "ASE_" . $Ase_Id;
                                        $checkTSE = $registrationModel->checkRepeatedUser($username);
                                        $is_dummy = 1;
                                        $tseenrollment->mobile_no     = NULL;
                                        $tseenrollment->is_dummy = 1;
                                        //  print_r($tseenrollment->is_dummy);
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $checkTSE = $registrationModel->checkUSER($tse_number);
                                        $is_dummy = 0;
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy = 0;
                                    }

                                    if (empty($checkTSE) && $checkTSE == NULL) {

                                        // $tseenrollment = new Registration();
                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id =
                                            $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;

                                        if ($tseenrollment->save()) {
                                            $Tse_Id = Yii::$app->db->getLastInsertId();
                                            $jtseenrollment = new Registration();

                                            if (($jtse_number == NULL)) {
                                                $username = "TSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy = 1;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy = 0;
                                            }


                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;

                                                $jtseenrollment->save();
                                                $Jtse_Id = Yii::$app->db->getLastInsertID();

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE  All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            } else {

                                                $checkJTSE->updated_date  = $date;

                                                $checkJTSE->save();
                                                $Jtse_Id = $checkJTSE['id'];

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            }
                                        }
                                    } else {
                                        //if tse registered 
                                        $checkTSE->username = $username;
                                        $checkTSE->updated_date  = $date;


                                        if ($checkTSE->save()) {
                                            $Tse_Id = $checkTSE['id'];
                                            $jtseenrollment = new Registration();

                                            if ($jtse_number == NULL) {
                                                $username = "TSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy = 1;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy = 0;
                                            }


                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;

                                                $jtseenrollment->save();
                                                $Jtse_Id = Yii::$app->db->getLastInsertID();

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            } else {
                                                $checkJTSE->updated_date  = $date;

                                                $checkJTSE->save();
                                                $Jtse_Id = $checkJTSE['id'];

                                                $approved = $approved + 1;

                                                $msgArray = [
                                                    'outlet_code' =>
                                                    $val['outlet_code'],
                                                    'message' =>
                                                    'ASM /ASE /TSE / JTSE All Are  Registred Sucessffully.'
                                                ];
                                                array_push($approvedUser, $msgArray);
                                            }
                                        }
                                    }
                                }
                            }
                        }



                        $outlet_user_mapping->outlet_id    = $outlet_id;
                        $outlet_user_mapping->asm_id       = $Asm_Id;
                        $outlet_user_mapping->ase_id       = $Ase_Id;
                        $outlet_user_mapping->tse_id       = $Tse_Id;
                        $outlet_user_mapping->jtse_id      = $Jtse_Id;
                        $outlet_user_mapping->is_dummy = 1;
                        $outlet_user_mapping->updated_date = $date;
                        $outlet_user_mapping->save(false);
                    }
                } else {
                    $rejected = count($import_data);
                    $msgArray = [
                        'outlet_code' => $val['outlet_code'],
                        'message' => ' OUTLET Already Registred Sucessffully.'
                    ];
                    array_push($rejectedUser, $msgArray);
                }
            }

            $responseData = [
                "approved" => $approved,
                "approved_List" => $approvedUser,
                "rejected" => $rejected,
                "rejected_List" => $rejectedUser

            ];
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw new HttpException(411, "$e");
        }

        return $responseData;
    }

    public function actionRbac(){
        // echo "test from action"; die;
        $token = $this->getBearerAccessToken();
        if (isset($token)) {
            $appHelper = new AppHelper();
            $checkUserDetails =  $appHelper->getUserDetails($token);

            if ($checkUserDetails != NULL || $checkUserDetails['access_token_expired_at'] > date('Y-m-d H:i:s'))
            {
                
                return "hii preeti";
            } else {
                $this->throwException(401, 'Unauthorized User Access');
            }
        } else {
            $this->throwException(411, 'The requested access_token could not be found');
        }
    }

    public function actionDisplaysum(){
        $a=2;
        $b=100;
        return $a+$b;
    }


    private function throwException($errCode, $errMsg)
    {
        throw new \yii\web\HttpException($errCode, $errMsg);
    }
}
