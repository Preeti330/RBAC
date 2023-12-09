<?php

namespace app\modules\v1\controllers;
use app\filters\auth\HttpBearerAuth;
use app\helpers\AppHelper;
use app\models\City;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\rest\ActiveController;
use yii\web\HttpException;
use yii\helpers\Url;
use yii\db\Query;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\helpers\Json;
use app\models\LoginForm;
use app\models\Message;
use app\models\CustomerNotification;
use app\models\Program;
use app\models\UserLogin;
use app\models\State;

use yii\web\UploadedFile;
use app\models\outlet;
use app\models\Outlet as ModelsOutlet;
use app\models\OutletUsersMapping;
use app\models\Region;
use app\models\Registration;
use app\models\TargetBrandWise;
use app\models\TargetConfig;
use app\models\UserOutlet;



class LoginController extends ActiveController
{
    public $modelClass = 'app\models\LoginForm';

        public function __construct($id, $module, $config = [])
        {
            parent::__construct($id, $module, $config);

        }

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
                    'login'     => ['post'],
                    'bsm-login' => ['post'],
                    'otp-verification' => ['post'],
                    'resendotp'     => ['post'],
                    'registration-upload'=>['post'],
                    'hub-login'=>['post']
                ],
            ];

            // remove authentication filter
            $auth = $behaviors['authenticator'];
            unset($behaviors['authenticator']);

            // add CORS filter
            $behaviors['corsFilter'] = [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    /*'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Allow-Origin' => ['*'],*/
                ],
            ];

            // re-add authentication filter
            $behaviors['authenticator'] = $auth;
            // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
            $behaviors['authenticator']['except'] = ['options', 'login','bsm-login','otp-verification',
            'resendotp',
            'registration-upload',
            'hub-login',
        ];


	        // setup access
	        $behaviors['access'] = [
		        'class' => AccessControl::className(),
		        'only' => ['index', 'view', 'create', 'update', 'delete'], //only be applied to
		        'rules' => [
			        [
				        'allow' => true,
				        'actions' => ['index', 'view', 'create', 'update', 'delete','login','bsm-login','otp-verification','resendotp'],
				        'roles' => ['admin', 'manageUsers'],
			        ],
			        [
			            'allow' => true,
			            'actions'   => ['me'],
			            'roles' => ['user']
			        ]
		        ],
	        ];

            return $behaviors;
        }
        public function auth()
        {
            return [
                'bearerAuth' => [
                    'class' => \yii\filters\auth\HttpBearerAuth::className(),
                ],
            ];
        }

        public function actionOptions($id = null) {
            return "ok";
        }

    /**
     * Generic function to throw HttpExceptions
     * @param $errCode
     * @param $errMsg
     * @author Suresh N
     */
    private function throwException($errCode, $errMsg)
    {
        throw new \yii\web\HttpException($errCode, $errMsg);
    }

    public function actionLogin()
    {

        
        $model = new LoginForm();
        $model->roles = [
            User::ROLE_HUBADMIN,
            User::ROLE_BRANCHMANAGER,
            User::ROLE_REPORTADMIN,
            // User::ROLE_AGENT,
            // User::ROLE_REPORTMANAGER,
            // User::ROLE_BRANCHMANAGER,
        ];

        //  $password='P123456';
        //  $pwd=Yii::$app->security->generatePasswordHash($password);
        //  print_r($pwd);exit;
       
      
        // Requires Login Form Objects
          
       
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
         
            $user = $model->getUser();
            $user->updated_date = date('Y-m-d H:i:s');
            $user->generateAccessTokenAfterUpdatingClientInfo();
            $response = \Yii::$app->getResponse();
            $response->setStatusCode(200);
            $id = implode(',', array_values($user->getPrimaryKey(true)));
            $user_role = User::findIdentity($id);

            $responseData = [
                'id' => (int) $id,
                'access_token' => $user->device_token,
                'user_name' => $user->username,
                'user_role_id' => $user->user_role_id,
            ];

            return $responseData;

        } else {
            $msg = "Username Or Password is not matching";          
            if(trim($model->username)==''){
                $msg = "Username cannot be empty";
            }else if(trim($model->password)==''){
                $msg = "Password cannot be empty";
            }

            $this->throwException(404, $msg);
        }
    }


    //cross verify 
    public function actionRegistrationUpload()
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

                $region_name = 
                             (!empty($val['Region']) && isset($val['Region']) && $val['Region'] != NULL) ? $val['Region'] :NULL;
                $city_name = 
                           (!empty($val['City']) && isset($val['City']) && $val['City'] != NULL) ? $val['City'] :NULL;
                $state_name =
                            (!empty($val['State']) && isset($val['State']) && $val['State'] != NULL) ? $val['State'] :NULL;           

                //var_dump($val['State']);
                $cityModel=new City();
                $getCityId=$cityModel->getCityDetails($city_name);
                $city_id=
                        (!empty($getCityId) && $getCityId != NULL && isset($getCityId))?$getCityId['id']:NULL;
                
                $stateModel=new State();        
                $getStateId=$stateModel->getStateDetails($state_name);    
                $state_id= 
                           (!empty($getStateId) && $getStateId != NULL && isset($getStateId))?$getStateId['id']:NULL; 
                           
                $getRegionDetails = Region::Getidbyname($region_name);
                $region_id=
                           (!empty($getRegionDetails) && $getRegionDetails != NULL && isset($getRegionDetails))?$getRegionDetails['id']:NULL;
             
                $outletenrollment = new Outlet();
                
                $checkOutlet = $outletenrollment->getOutletDetailsOnOutletCode($val['outlet_code']);
                       
                if (empty($checkOutlet) && $checkOutlet == NULL) {
                    $outletenrollment = new Outlet();
                    $outletenrollment->rocode         = (string) $val['outlet_code'];
                    $outletenrollment->roname         = (string) $val['outlet_name'];
                    $outletenrollment->region_id      = $region_id;
                    $outletenrollment->channel_id     = 1;
                    $outletenrollment->depot_dbf      = (string) $val['Depot'];

                    $outletenrollment->save();

                    $outlet_id = Yii::$app->db->getLastInsertId();
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

                    //take name golbally ,
                    $asm_name =
                              (!empty($val['ASM_Name']) && isset($val['ASM_Name']) && $val['ASM_Name'] != NULL) ? $val['ASM_Name'] : NULL;
                    $ase_name =
                              (!empty($val['ASE_Name']) && isset($val['ASE_Name']) && $val['ASE_Name'] != NULL) ? $val['ASE_Name'] : NULL;
                    $tse_name =
                              (!empty($val['TSE_Name']) && isset($val['TSE_Name']) && $val['TSE_Name'] != NULL) ? $val['TSE_Name'] :NULL; 
                              
                    $jtse_name =
                              (!empty($val['JTSE_Name']) && isset($val['JTSE_Name']) && $val['JTSE_Name'] != NULL) ? $val['JTSE_Name'] : NULL;           
                             
                    

 
                    if (empty($checkASM) && $checkASM == NULL) {
                        // echo "asm : 1  ".$val['ASM_Mobile_Number']."<br>";
                        $asm_number = $val['ASM_Mobile_Number'];
                        $asmenrollment = new Registration();
                        $asmenrollment->username      = $val['ASM_Name'];
                        $asmenrollment->mobile_no     = "$asm_number";
                        $asmenrollment->user_role_id  = 4;
                        $asmenrollment->email_id    = $val['ASM_Email_Id'];
                        $asmenrollment->updated_date  = $date;
                        $asmenrollment->is_dummy  =0;
                        $asmenrollment->state_id  = $state_id;
                        $asmenrollment->city_id  =  $city_id;
                        $asmenrollment->display_name  =  $asm_name;
                        $asmenrollment->region_id  =  $region_id;
                        
                       

                        if ($asmenrollment->save()) {
                            $Asm_Id = Yii::$app->db->getLastInsertId();

                            $aseenrollment = new Registration();
                            if ($ase_number == NULL) {
                                $username = "ASE_" . $Asm_Id;
                                $is_dummy = 1;
                                $checkASE = $registrationModel->checkRepeatedUser($username);
                                $aseenrollment->mobile_no     = NULL;
                                $aseenrollment->is_dummy=1;
                                $aseenrollment->display_name  =  $asmenrollment->display_name;
                               
                            } else {
                                $username = $val['ASE_Name'];
                                $is_dummy = 0;
                                $checkASE = $registrationModel->checkUSER($ase_number);
                                $aseenrollment->mobile_no     = "$ase_number";
                                $aseenrollment->is_dummy=0;
                                $aseenrollment->display_name  =  $ase_name;
                                
                            }
                           

                            if (empty($checkASE) && $checkASE == NULL) {  
                               
                                $aseenrollment->username      = $username;
                                // $aseenrollment->mobile_no     = $ase_number;
                                $aseenrollment->user_role_id  = 5;
                                $aseenrollment->email_id     = $val['ASE_Email_Id'];
                                $aseenrollment->updated_date  = $date;
                                $aseenrollment->state_id  = $state_id;
                                $aseenrollment->city_id  =  $city_id;
                                $aseenrollment->region_id  =  $region_id;

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
                                        $username = "TSE_" . $Ase_Id;
                                        $is_dummy = 1;
                                        $checkTSE =    $registrationModel->checkRepeatedUser($username);
                                        $tseenrollment->mobile_no     = NULL;
                                        $tseenrollment->is_dummy=1;
                                        $tseenrollment->display_name  =  $aseenrollment->display_name;
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $is_dummy = 0;
                                        $checkTSE = $registrationModel->checkUSER($tse_number);
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy=0;
                                        $tseenrollment->display_name  =$tse_name;
                                    }

                                    if (empty($checkTSE) && $checkTSE == NULL) {
                                       
                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id     = $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;
                                        $tseenrollment->state_id  = $state_id;
                                        $tseenrollment->city_id  =  $city_id;
                                        $tseenrollment->region_id  =  $region_id;

                                        if ($tseenrollment->save(false)) {
                                            $Tse_Id = Yii::$app->db->getLastInsertID();
                                            $jtseenrollment = new Registration();

                                            if ($jtse_number == NULL) {
                                                $username = "JTSE_" . $Tse_Id;
                                                $is_dummy = 1;
                                                $checkJTSE =    $registrationModel->checkRepeatedUser($username);
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy=1;
                                                $jtseenrollment->display_name  =  $tseenrollment->display_name;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $is_dummy = 0;
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy=0;  
                                                $jtseenrollment->display_name=$jtse_name;     
                                            }
                                           

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
       
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;
                                                $jtseenrollment->state_id  = $state_id;
                                                $jtseenrollment->city_id  =  $city_id;
                                                $jtseenrollment->region_id  = $region_id;
                                                

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
                                                $Jtse_Id=$checkJTSE['id'];
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
                        }else{
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
                        $checkASM->is_dummy  =0;
                        

                        if ($checkASM->save()) {
                            $Asm_Id = $checkASM['id'];
                            $aseenrollment = new Registration();

                            if ($ase_number == NULL) {
                                $username = "ASE_" . $Asm_Id;
                                $is_dummy = 1;
                                $checkASE = $registrationModel->checkRepeatedUser($username);
                                $aseenrollment->mobile_no     =NULL;
                                $aseenrollment->is_dummy=1;
                                $aseenrollment->display_name  = $asm_name;
                                
                            } else {
                                $username = $val['ASE_Name'];
                                $is_dummy = 0;
                                $checkASE = $registrationModel->checkUSER($ase_number);
                                $aseenrollment->mobile_no     ="$ase_number";
                                $aseenrollment->is_dummy=0;
                                $aseenrollment->display_name  = $ase_name;
                               
                            }

                       

                            if (empty($checkASE) && $checkASE == NULL) {
                                
                                $aseenrollment->username      = $username;
                                // $aseenrollment->mobile_no     = $ase_number;
                                $aseenrollment->user_role_id  = 5;
                                $aseenrollment->email_id     = $val['ASE_Email_Id'];
                                $aseenrollment->updated_date  = $date;
                                $aseenrollment->state_id  = $state_id;
                                $aseenrollment->city_id  =  $city_id;
                                $aseenrollment->region_id  = $region_id;

                                if ($aseenrollment->save()) {
                                    
                                    $Ase_Id = Yii::$app->db->getLastInsertId();

                                    $tseenrollment = new Registration();
                                    if (($tse_number == NULL)) {
                                        $username = "TSE_" . $Ase_Id;
                                        $checkTSE = $registrationModel->checkRepeatedUser($username);
                                        $is_dummy = 1;
                                        $tseenrollment->is_dummy=1;
                                        $tseenrollment->display_name  = $aseenrollment->display_name;
                                        $tseenrollment->mobile_no     = NULL;
                                      //  var_dump($tseenrollment->display_name);
                                       
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $checkTSE =
                                            $registrationModel->checkUSER($tse_number);
                                        $is_dummy = 0;
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy=0;
                                        $tseenrollment->display_name  = $tse_name;
                                        
                                    }
                                   
                                    if (empty($checkTSE) && $checkTSE == NULL) {
                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id     =
                                            $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;
                                        $tseenrollment->state_id  = $state_id;
                                        $tseenrollment->city_id  =  $city_id;
                                        $tseenrollment->region_id  = $region_id;
                                    //    $tseenrollment->display_name  =  'preeti';

                                        
                                        if ($tseenrollment->save()) {
                                            $Tse_Id = Yii::$app->db->getLastInsertId();
                                            $jtseenrollment = new Registration();
                                            if (($jtse_number == NULL)) {
                                                $username = "JTSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy=1;
                                                $jtseenrollment->display_name  = $tseenrollment->display_name;
                                                

                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy=0;
                                                $jtseenrollment->display_name  = $jtse_name;
                                            }


                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                
                                                $jtseenrollment->username      = $username;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;
                                                $jtseenrollment->state_id  = $state_id;
                                                $jtseenrollment->city_id  =  $city_id;
                                                $jtseenrollment->region_id  = $region_id;

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
                                        else{
                                            return $tseenrollment->errors;
                                        }
                                    } else {
                                        //if tse registered 
                                        $checkTSE->updated_date  = $date;
                                        
                                        if ($checkTSE->save()) {
                                            $Tse_Id = $checkTSE['id'];
                                            $jtseenrollment = new Registration();

                                            if (($jtse_number == NULL)) {
                                                $username = "JTSE_" . $Tse_Id;
                                                $checkJTSE =         $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy=1;
                                                $jtseenrollment->display_name  = $tseenrollment->display_name;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy=0;
                                                $jtseenrollment->display_name  = $tse_name;
                                            }

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;
                                                $jtseenrollment->state_id  = $state_id;
                                                $jtseenrollment->city_id  =  $city_id;
                                                $jtseenrollment->region_id  = $region_id;
                                                

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
                                        $username = "TSE_" . $Ase_Id;
                                        $checkTSE = $registrationModel->checkRepeatedUser($username);
                                        $is_dummy = 1;
                                        $tseenrollment->mobile_no     = NULL;
                                        $tseenrollment->is_dummy=1;
                                        $tseenrollment->display_name  = $aseenrollment->display_name;
                                    
                                    } else {
                                        $username = $val['TSE_Name'];
                                        $checkTSE = $registrationModel->checkUSER($tse_number);
                                        $is_dummy = 0;
                                        $tseenrollment->mobile_no     = "$tse_number";
                                        $tseenrollment->is_dummy=0;  
                                        $tseenrollment->display_name  = $tse_name;                   
                                    }
                                    
                                  
                                    if (empty($checkTSE) && $checkTSE == NULL) {

                                        // $tseenrollment = new Registration();
                                        $tseenrollment->username      = $username;
                                        // $tseenrollment->mobile_no     = $tse_number;
                                        $tseenrollment->user_role_id  = 6;
                                        $tseenrollment->email_id =
                                            $val['TSE_Email_Id'];
                                        $tseenrollment->updated_date  = $date;
                                        $tseenrollment->state_id  = $state_id;
                                        $tseenrollment->city_id  =  $city_id;
                                        $tseenrollment->region_id  = $region_id;
                                        

                                        if ($tseenrollment->save()) {
                                            $Tse_Id = Yii::$app->db->getLastInsertId();
                                            $jtseenrollment = new Registration();

                                            if (($jtse_number == NULL)) {
                                                $username = "JTSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy=1;
                                                $jtseenrollment->display_name  = $tseenrollment->display_name;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy=0;
                                                $jtseenrollment->display_name  = $jtse_name;
                                            }

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;
                                                $jtseenrollment->state_id  = $state_id;
                                                $jtseenrollment->city_id  =  $city_id;
                                                $jtseenrollment->region_id  = $region_id;

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
                                                $username = "JTSE_" . $Tse_Id;
                                                $checkJTSE = $registrationModel->checkRepeatedUser($username);
                                                $is_dummy = 1;
                                                $jtseenrollment->mobile_no     = NULL;
                                                $jtseenrollment->is_dummy=1;
                                                $jtseenrollment->display_name  = $tseenrollment->display_name;
                                            } else {
                                                $username = $val['JTSE_Name'];
                                                $checkJTSE = $registrationModel->checkUSER($jtse_number);
                                                $is_dummy = 0;
                                                $jtseenrollment->mobile_no     = "$jtse_number";
                                                $jtseenrollment->is_dummy=0;
                                                $jtseenrollment->display_name  = $jtse_name;
                                            }

                                            if (empty($checkJTSE) && $checkJTSE == NULL) {
                                                // $jtseenrollment = new Registration();
                                                $jtseenrollment->username      = $username;
                                                // $jtseenrollment->mobile_no     = $jtse_number;
                                                $jtseenrollment->user_role_id  = 7;
                                                $jtseenrollment->email_id   = $val['JTSE_Email_Id'];

                                                $jtseenrollment->updated_date  = $date;
                                                $jtseenrollment->state_id  = $state_id;
                                                $jtseenrollment->city_id  =  $city_id;
                                                $jtseenrollment->region_id  = $region_id;

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

}
