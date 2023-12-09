<?php
namespace app\models;

use Firebase\JWT\JWT;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\rbac\Permission;
use yii\web\HttpException;
use yii\web\Request as WebRequest;

use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property int $program_id
 * @property integer $user_role_id
 * @property string $username
 * @property string $password_hash
 * @property string $imp_code 
 * @property string $auth_key
 * @property string|null $access_token_expired_at
 * @property integer $member_type_id
 * @property integer $geographical_id
 * @property integer $region_id
 * @property integer $state_id
 * @property integer $city_id
 * @property integer $asm_user_id 
 * @property integer $bsm_user_id
 * @property integer $rsm_user_id
 * @property integer $zsm_user_id
 * @property integer $is_demo_number
 * @property integer $is_block
 * @property integer $is_common_terms
 * @property integer $is_first_login
 * @property integer $is_terms_accept
 * @property integer $is_video_watched
 * @property date $on_board_date
 * @property string $on_board_week
 * @property int $status
 * @property int|null $created_by
 * @property string|null $created_date
 * @property int|null $updated_by
 * @property string|null $updated_date
 * @property string|null terms_accept_date
 */

class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface{

    const ROLE_HUBADMIN = 1;
    const ROLE_AGENT = 7;
    const ROLE_REPORTMANAGER  = 8;
    const ROLE_BRANCHMANAGER  = 9;

    
    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;

    /** @var  string to store JSON web token */
    public $access_token;

    /** @var  array $permissions to store list of permissions */
    public $permissions;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(){
        return [
            [['program_id','user_role_id','username','first_name'], 'required'],
            [['id', 'program_id', 'user_role_id', 'member_type_id', 'geographical_id', 'region_id', 'state_id', 'city_id', 'se_user_id', 'asm_user_id', 'bsm_user_id', 'rsm_user_id', 'zsm_user_id', 'is_static_otp','is_demo_number', 'is_block', 'is_common_terms', 'is_first_login', 'is_terms_accept', 'is_video_watched', 'supervisor', 'status', 'created_by', 'updated_by','source_from','enrollment_status'], 'integer'],
            [['username'], 'string', 'max' => 25],
            [['imp_code'], 'string', 'max' => 50],            
            [['password_hash','first_name','last_name','email_id', 'auth_key', 'user_detail_name'], 'string', 'max' => 255],
            [['device_token'], 'string', 'max' => 512],
            [['default_lang'], 'string', 'max' => 2],
            [['last_login_ip'], 'string', 'max' => 20],
            [['pincode', 'address', 'billing_address', 'shipping_address', 'fcm_token', 'app_version', 'on_board_week', 'profile_image'], 'string'],
            [['dob', 'anniversary_date', 'access_token_expired_at', 'last_login_at', 'confirmed_at', 'blocked_at', 'on_board_date','created_date', 'updated_date','terms_accept_date'], 'safe'],
            [['dob', 'anniversary_date'], 'date', 'format'=>'php:Y-m-d'], 
            [['is_verified'],'boolean'],           
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(){
        return [
            'id' => 'ID',
            'program_id' => 'Program ID',
            'user_role_id' => 'User Role',
            'username' => 'UserName',
            'password_hash' => 'Password',
            'imp_code' => 'Imp Code', 
            'first_name' => 'First Name', 
            'last_name' => 'Last Name', 
            'address' => 'Address', 
            'billing_address' => 'Billing Address', 
            'shipping_address' => 'Shipping Address',
            'pincode' => 'Pincode',
            'dob' => 'Date of Birth', 
            'anniversary_date' => 'Anniversary Date', 
            'email_id' => 'Email ID', 
            'member_type_id' => 'Member Type',
            'geographical_id' => 'Geographical Id',
            'region_id' => 'Region Id',
            'state_id' => 'State Id',
            'city_id' => 'City Id',
            'se_user_id' => 'SE User Id',
            'asm_user_id' => 'Asm User Id', 
            'bsm_user_id' => 'Bsm User Id',
            'rsm_user_id' => 'Rsm User Id',
            'zsm_user_id' => 'Zsm User Id',
            'device_token' => 'Device Token', 
            'fcm_token' => 'FCM Token', 
            'app_version' => 'App Version',
            'auth_key' => 'Auth Key',
            'access_token_expired_at' => 'Access Token Expired At', 
            'is_verified' => 'is Verified',
            'supervisor' => 'Supervisor', 
            'default_lang' => 'Default Lang',    
            'last_login_at' => 'Last Login At',
            'last_login_ip' => 'Last Login Ip', 
            'confirmed_at' => 'Confirmed At', 
            'blocked_at' => 'Blocked At',
            'is_static_otp' => 'Is Static Otp',
            'is_demo_number' => 'Is Demo Number',
            'is_block' => 'Is Block',
            'is_common_terms' => 'Is Common Terms',
            'is_first_login' => 'Is First Login',
            'is_terms_accept' => 'Is Terms Accept',
            'is_video_watched' => 'Is Video Watched',
            'on_board_date' => 'On Board Date', 
            'on_board_week' => 'On Board Week',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_date' => 'Created Date',
            'updated_by' => 'Updated By',
            'updated_date' => 'Updated Date',
            'source_from' => 'Source From',
            'terms_accept_date' => 'Terms Accept Date',
            'profile_image' => 'Profile Picture',
            'user_detail_name' => 'User Full Name',
            'enrollment_status'=>'Enrollment Status'
        ];
    }

    
    /**
     * @return bool Whether the user is confirmed or not.
     */
    public function getIsConfirmed() {
        return $this->confirmed_at != null;
    }

    /**
     * @return bool Whether the user is blocked or not.
     */
    public function getIsBlocked() {
        return $this->blocked_at != null;
    }

    public static function findIdentity($id) {
       
        $user = static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
        if ($user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)) {
            return null;
        }
        return $user;
    }

    public static function findIdentityWithoutValidation($id) {
        $user = static::findOne(['id' => $id]);
        return $user;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */

    public static function findByUsername($username) {
        $user = static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        if ($user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)) {
            return null;
        }
        return $user;
    }

    /**
     * Finds user by username
     *
     * @param string $usernamet
     * @param array $roles
     * @return static|null
     */
    public static function findByUsernameWithRoles($username, $roles) {
        /** @var User $user */
        $user = static::find()->where([
            'username' => $username,
            'status' => self::STATUS_ACTIVE,

        ])->andWhere(['in', 'user_role_id', $roles])->one();

        if ($user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)) {
            return null;
        }

        return $user;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */

    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    
    /**
     * Generate access token
     *  This function will be called every on request to refresh access token.
     *
     * @param bool $forceRegenerate whether regenerate access token even if not expired
     *
     * @return bool whether the access token is generated or not
     */
    public function generateAccessTokenAfterUpdatingClientInfo($forceRegenerate = false) {
        // update client login, ip
        $this->last_login_ip = Yii::$app->request->userIP;
        $this->last_login_at = new Expression('NOW()');

        // check time is expired or not
        if ($forceRegenerate == true
            || $this->access_token_expired_at == null
            || (time() > strtotime($this->access_token_expired_at))) {
            // generate access token
            $this->generateAccessToken();
        }
        $this->save(false);
        return true;
    }

    public function generateAccessToken() {
        // generate access token
        //        $this->access_token = Yii::$app->security->generateRandomString();
        $tokens = $this->getJWT();
        $this->device_token = $tokens[0]; // Token
        $this->access_token_expired_at = date("Y-m-d H:i:s", $tokens[1]['exp']); // Expire
    }

    public function generateAccessTokenBSM($mobile,$user_id) {
        // generate access token
        //        $this->access_token = Yii::$app->security->generateRandomString();
        $tokens = $this->getJWT($mobile,$user_id);
        $this->device_token = $tokens[0]; // Token 
        $this->access_token_expired_at = date("Y-m-d H:i:s", $tokens[1]['exp']); // Expire
    }

    public function beforeSave($insert) {
        // Convert username to lower case
        if(isset($this->username))
            $this->username = strtolower($this->username);
        // Fill auth key if empty
        if ($this->auth_key == '') {
            $this->generateAuthKey();
        }
        return parent::beforeSave($insert);
    }

    public function getPassword() {
        return '';
    }



    /*
             * JWT Related Functions
    */

    /**
     * Store JWT token header items.
     * @var array
     */
    protected static $decodedToken;

    protected static function getSecretKey() {
        return Yii::$app->params['jwtSecretCode'];
    }

    // And this one if you wish
    protected static function getHeaderToken() {
        return [];
    }

    /**
     * Logins user by given JWT encoded string. If string is correctly decoded
     * - array (token) must contain 'jti' param - the id of existing user
     * @param  string $accessToken access token to decode
     * @return mixed|null          User model or null if there's no user
     * @throws \yii\web\ForbiddenHttpException if anything went wrong
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $secret = static::getSecretKey();
        // Decode token and transform it into array.
        // Firebase\JWT\JWT throws exception if token can not be decoded
        try {
            $decoded = JWT::decode($token, $secret, [static::getAlgo()]);
        } catch (\Exception $e) {
            return false;
        }
        static::$decodedToken = (array) $decoded;
        // If there's no jti param - exception
        if (!isset(static::$decodedToken['jti'])) {
            return false;
        }
        // JTI is unique identifier of user.
        // For more details: https://tools.ietf.org/html/rfc7519#section-4.1.7
        $id = static::$decodedToken['jti'];
        return static::findByJTI($id);
    }

    /**
     * Finds User model using static method findOne
     * Override this method in model if you need to complicate id-management
     * @param  string $id if of user to search
     * @return mixed       User model
     */
    public static function findByJTI($id) {
        /** @var User $user */
        $user = static::find()->where([
            '=', 'id', $id,
        ])
            ->andWhere([
                '=', 'status', self::STATUS_ACTIVE,
            ])
            ->andWhere([
                '>', 'access_token_expired_at', new Expression('NOW()'),
            ])->one();
        if ($user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)) {
            return null;
        }
        return $user;
    }

    /**
     * Getter for encryption algorytm used in JWT generation and decoding
     * Override this method to set up other algorytm.
     * @return string needed algorytm
     */
    public static function getAlgo() {
        return 'HS256';
    }

    /**
     * Returns some 'id' to encode to token. By default is current model id.
     * If you override this method, be sure that findByJTI is updated too
     * @return integer any unique integer identifier of user
     */
    public function getJTI() {
        return $this->getId();
    }

    /**
     * Encodes model data to create custom JWT with model.id set in it
     * @return array encoded JWT
     */
    public function getJWT() {
        // Collect all the data
        $secret = static::getSecretKey();
        $currentTime = time();
        $expire = $currentTime + 86400; // 1 day
        $request = Yii::$app->request;
        $hostInfo = '';
        // There is also a \yii\console\Request that doesn't have this property
        if ($request instanceof WebRequest) {
            $hostInfo = $request->hostInfo;
        }
        // Merge token with presets not to miss any params in custom
        // configuration
        $token = array_merge([
            'iat' => $currentTime, // Issued at: timestamp of token issuing.
            'iss' => $hostInfo, // Issuer: A string containing the name or identifier of the issuer application. Can be a domain name and can be used to discard tokens from other applications.
            'aud' => $hostInfo,
            'nbf' => $currentTime, // Not Before: Timestamp of when the token should start being considered valid. Should be equal to or greater than iat. In this case, the token will begin to be valid 10 seconds
            'exp' => $expire, // Expire: Timestamp of when the token should cease to be valid. Should be greater than iat and nbf. In this case, the token will expire 60 seconds after being issued.
            'data' => [
                'username' => $this->username,
                'lastLoginAt' => $this->last_login_at,
                'program_id' => $this->program_id,
            ],
        ], static::getHeaderToken());
        // Set up id
        $token['jti'] = $this->getJTI(); // JSON Token ID: A unique string, could be used to validate a token, but goes against not having a centralized issuer authority.
        return [JWT::encode($token, $secret, static::getAlgo()), $token];
    }

     /**
     * Generic function to throw HttpExceptions
     * @param $errCode
     * @param $errMsg
     * @author Vijay Bhaskar K
     */
    private function throwException($errCode, $errMsg)
    {
        throw new \yii\web\HttpException($errCode, $errMsg);
    }

    public function list_search_users($params){
        $mobile_data = $aid_data = $user_type_data = $search_data = $region_data = $state_data = $city_data = $user_data = $member_type_data = '';

        $user_type_list = isset($params['user_role_id'])?$params['user_role_id']:$params['user_role_list'];        
        $search         = isset($params['search_string'])?$params['search_string']:'';
        $mobile_no      = isset($params['mobile_no'])?$params['mobile_no']:'';
        $aid_no         = isset($params['aid_no'])?$params['aid_no']:'';
        $region_ids     = isset($params['region_ids'])?$params['region_ids']:'';
        $state_ids      = isset($params['state_ids'])?$params['state_ids']:'';
        $city_ids       = isset($params['city_ids'])?$params['city_ids']:'';
        $user_id        = isset($params['user_id'])?$params['user_id']:'';
        $page_length    = isset($params['page_length'])?$params['page_length']:'';
        $excel_flag     = isset($params['excel_flag'])?$params['excel_flag']:'0';

        $member_type_ids = isset($params['member_type_id'])?$params['member_type_id']:'';  


             

        if(trim($mobile_no)!=''){
            $mobile_data = " Userr.username='".$mobile_no."' ";
        }

        if(trim($aid_no)!=''){
            $aid_data = " Userr.imp_code='".$aid_no."' ";
        }

        //$basic_user_types = array(6);

        $user_type_filter= explode(',',$user_type_list);
        if(trim($user_type_list)!=''){
            $user_type_data = " AND Userr.user_role_id in('".implode("','",$user_type_filter)."') ";
        }

        $region_filter= explode(',',$region_ids);    
        if(trim($region_ids)!=''){
            $region_data = " AND Userr.region_id in('".implode("','",$region_filter)."') ";
        }

        $state_filter= explode(',',$state_ids);
        if(trim($state_ids)!=''){
            $state_data = " AND Userr.state_id in('".implode("','",$state_filter)."') ";
        }

        $city_filter= explode(',',$city_ids);
        if(trim($city_ids)!=''){
            $city_data = " AND Userr.city_id in('".implode("','",$city_filter)."') ";
        }


        if(!empty($search)){
            $search_data = " AND (Userr.username ILIKE '%". $search . "%' OR Userr.user_detail_name ILIKE '%". $search . "%' OR Userr.imp_code ILIKE '%". $search . "%') ";
        }  


        if(!empty($user_id)){
            $user_data = "AND Userr.id = '".$user_id."' ";
        }


        $member_filter = explode(',',$member_type_ids);
        if(trim($member_type_ids)!=''){
            $member_type_data = " AND Userr.member_type_id in('".implode("','",$member_filter)."') ";
        }

        $sql="SELECT row_number() over (order by LENGTH(Userr.imp_code), Userr.imp_code) as sl_no,
                     Userr.id as user_id,
                     UserRoles.role_name,
                     Userr.imp_code,
                     Userr.username as mobile_no,
                     Userr.user_detail_name,
                     Userr.user_detail_name as company_name,
                     MemberType.member_desc as member_type, 
                     Userr.email_id,
                     Userr.address,                         
                     Region.region_name,
                     State.state_name, 
                     City.city_name,
                     Branches.branch,
                     Branches.branch_code,
                     SeUserr.user_detail_name as se_name,
                     SeUserr.username as se_mobile_no,
                     SeUserr.email_id as se_mail_id,
                     AsmUserr.user_detail_name as asm_name,
                     AsmUserr.username as asm_mobile_no,
                     AsmUserr.email_id as asm_mail_id,
                     BsmUserr.user_detail_name as bsm_name,
                     BsmUserr.username as bsm_mobile_no,
                     BsmUserr.email_id as bsm_mail_id,
                     RsmUserr.user_detail_name as rsm_name,
                     RsmUserr.username as rsm_mobile_no,
                     ZsmUserr.user_detail_name as zsm_name,
                     ZsmUserr.username as zsm_mobile_no,
                    (CASE WHEN Userr.status='1' THEN 'ON BOARDED' 
                         WHEN Userr.status='0' THEN 'YET TO BOARD' 
                         ELSE  'CANCELLED' END) AS  on_board_status,
                    Userr.on_board_date
            FROM users AS Userr
            JOIN user_roles AS UserRoles ON (UserRoles.id = Userr.user_role_id and UserRoles.status=1 and UserRoles.is_display=1)  
            LEFT JOIN member_types AS MemberType ON (MemberType.id = Userr.member_type_id)           
            LEFT JOIN regions AS Region ON (Region.id = Userr.region_id)
            LEFT JOIN states AS State ON (State.id = Userr.state_id) 
            LEFT JOIN cities AS City ON (City.id = Userr.city_id)
            LEFT JOIN branch AS Branches ON (Branches.id = Userr.branch_id)          
            LEFT JOIN users  As SeUserr ON (SeUserr.id = Userr.se_user_id)       
            LEFT JOIN users  As AsmUserr ON (AsmUserr.id = Userr.asm_user_id) 
            LEFT JOIN users  As BsmUserr ON (BsmUserr.id = Userr.bsm_user_id)
            LEFT JOIN users  As RsmUserr ON (RsmUserr.id = Userr.rsm_user_id)
            LEFT JOIN users  As ZsmUserr ON (ZsmUserr.id = Userr.zsm_user_id)            
            Where Userr.status>=0  ".$member_type_data.$mobile_data.$aid_data.$user_type_data.$search_data.$region_data.$state_data.$city_data.$user_data." 
            order by LENGTH(Userr.imp_code), Userr.imp_code ";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        

        if($excel_flag=='1' || $user_id!=''){
            return  $data;
        }else{
            if($page_length==''){
                $page_length = 5;
            }
           $provider = new ArrayDataProvider([
              'allModels' => $data,              
              'pagination' => [
              'defaultPageSize' => 2,
              'pageSize' => $page_length,
              'pageSizeLimit' => [1, 2],
              ],
              
           ]);
           return  $provider;
        }
    }


    #@written by Vijay Bhaskar
    #inserting outlets into userdetails table.
    public function insertCtplyUser($params){
        $add_users = new Users();
        foreach($params as $pkey=>$pval){
            $add_users->$pkey = $pval;  
        }        
        if ($add_users->save(false)) {
            return $add_users->id;
        } else {
            throw new HttpException(422, json_encode($add_users->errors));
        }
    } 

    public function EnrolledUsers($bsm_user_id,$program_id,$status='',$territory='',$state='',$city='',$branch='',$start_date='',$end_date=''){
        $region = $branch_id =  $city_id =  $state_id = $dateFilter = '';
        if(!empty($status)){

            $enrollment_status =" AND Usr.enrollment_status =".$status."";
        }else{
            $enrollment_status = " AND Usr.enrollment_status IN (1,2,3)";
        }
        if(!empty($territory)){
            $region = " AND Usr.region_id =".$territory."";
        }
        if(!empty($state)){
            $state_id = " AND Usr.state_id =".$state."";
        }
        if(!empty($city)){
            $city_id = " AND Usr.city_id =".$city."";
        }
        if(!empty($branch)){
            $branch_id = " AND Usr.branch_id =".$branch."";
        }
        if(!empty($start_date) && !empty($end_date)){
            $dateFilter = " AND Usr.enrollment_date BETWEEN '".$start_date."' and '".$end_date."' ";
        }
        $userdetails = "SELECT Usr.id as user_id,Usr.region_id,Usr.state_id,Usr.city_id,Usr.branch_id,Usr.username as mobile_number,Usr.first_name,Usr.last_name,
            Rgn.region_name,Sts.state_name,Cts.city_name,Usr.enrollment_date,Usr.is_enrolled,Usr.enrolled_by as enrolled_by_user_id,EnrolledBy.first_name as enrolled_by,
            Usr.reject_count,Usr.company_name as firm_name,Usr.rejection_reason,Usr.id_number as unique_id_number,
            CASE WHEN Usr.enrollment_status = 0 THEN 'Pending'
            WHEN Usr.enrollment_status = 1 THEN 'Enrolled'
            WHEN Usr.enrollment_status = 2 THEN 'Approved'
            WHEN Usr.enrollment_status = 3 THEN 'Rejected'
            ELSE 'Unknown Status' END AS enrollment_status, Usr.dob,Usr.anniversary_date,Usr.email_id,
            Files.id_card as unique_id_photo,Files.visiting_card,Brch.branch,Usr.address,Usr.pincode,
            Usr.billing_address,Usr.shipping_address,
            CASE WHEN Usr.member_type_id=2 THEN 'Solitaire'
            WHEN Usr.member_type_id=1 THEN 'Bespoke'
            ELSE 'not set' END as aid_type,Usr.member_type_id,Usr.gender as gender_id,
            CASE WHEN Usr.gender =1 THEN 'Male'
            WHEN Usr.gender = 2 THEN 'Female'
            WHEN Usr.gender = 3 THEN 'Other'
            ELSE 'Not Set' END as gender,Usr.whatsapp_number,Usr.alternative_number,
            CASE WHEN Usr.unique_id_type= 1 THEN 'Adhaar CARD'
            WHEN Usr.unique_id_type= 2 THEN 'PAN Card'
            WHEN Usr.unique_id_type= 3 THEN 'Voters ID Card'
            WHEN Usr.unique_id_type= 4  THEN 'Driving License'
            ELSE 'Not Set' END AS unique_id_type,Usr.unique_id_type as unique_id_type_id,
            Usr.area,Usr.user_detail_name as display_name, ASM.user_detail_name as asm_name,
            ASM.username as asm_number, SE.user_detail_name as se_name, SE.username as se_mobile_number 
            FROM users as Usr
            LEFT JOIN user_enrollment_files as Files ON (Files.user_id = Usr.id)
            LEFT JOIN regions as Rgn ON (Rgn.id = Usr.region_id)
            LEFT JOIN states as Sts ON (Sts.id = Usr.state_id)
            LEFT JOIN cities as Cts ON (Cts.id = Usr.city_id)
            LEFT JOIN branch as Brch ON (Brch.id = Usr.branch_id)
            LEFT JOIN users as EnrolledBy ON (EnrolledBy.id = Usr.enrolled_by)
            LEFT JOIN users as ASM ON (ASM.id = Usr.asm_user_id)
            LEFT JOIN users as SE ON(SE.id = Usr.se_user_id) WHERE Usr.program_id = ".$program_id."  AND Usr.bsm_user_id = ".$bsm_user_id."
            $state_id $enrollment_status $region $city_id $branch_id $dateFilter order by Usr.id DESC";
            // echo $userdetails;exit;
            $data = Yii::$app->db->createCommand($userdetails)->queryAll();   
            
            $provider = new ArrayDataProvider([
                'allModels' => $data,              
                'pagination' => [
                'defaultPageSize' => 2,
                'pageSize' => 50,
                'pageSizeLimit' => [1, 2],
                ],
                
             ]);         
            return $provider;
    }
    public function EnrolledUserbyid($user_id){
        $userdetails = "SELECT Usr.id as user_id,Usr.region_id,Usr.state_id,Usr.city_id,Usr.branch_id,Usr.username as mobile_number,Usr.first_name,Usr.last_name,
            Rgn.region_name,Sts.state_name,Cts.city_name,Usr.enrollment_date,Usr.is_enrolled,Usr.enrolled_by as enrolled_by_user_id,EnrolledBy.first_name as enrolled_by,
            Usr.reject_count,Usr.company_name as firm_name,Usr.rejection_reason,Usr.id_number as unique_id_number,
            CASE WHEN Usr.enrollment_status = 0 THEN 'Pending'
            WHEN Usr.enrollment_status = 1 THEN 'Enrolled'
            WHEN Usr.enrollment_status = 2 THEN 'Approved'
            WHEN Usr.enrollment_status = 3 THEN 'Rejected'
            ELSE 'Unknown Status' END AS enrollment_status, Usr.dob,Usr.anniversary_date,Usr.email_id,
            Files.id_card as unique_id_photo,Files.visiting_card,Brch.branch,Usr.address,Usr.pincode,
            Usr.billing_address,Usr.shipping_address,
            CASE WHEN Usr.member_type_id=2 THEN 'Solitaire'
            WHEN Usr.member_type_id=1 THEN 'Bespoke'
            ELSE 'not set' END as aid_type,Usr.member_type_id,Usr.gender as gender_id,
            CASE WHEN Usr.gender =1 THEN 'Male'
            WHEN Usr.gender = 2 THEN 'Female'
            WHEN Usr.gender = 3 THEN 'Other'
            ELSE 'Not Set' END as gender,Usr.whatsapp_number,Usr.alternative_number,
            CASE WHEN Usr.unique_id_type= 1 THEN 'Adhaar CARD'
            WHEN Usr.unique_id_type= 2 THEN 'PAN Card'
            WHEN Usr.unique_id_type= 3 THEN 'Voters ID Card'
            WHEN Usr.unique_id_type= 4  THEN 'Driving License'
            ELSE 'Not Set' END AS unique_id_type,Usr.unique_id_type as unique_id_type_id,
            Usr.area
            FROM users as Usr
            LEFT JOIN user_enrollment_files as Files ON (Files.user_id = Usr.id)
            LEFT JOIN regions as Rgn ON (Rgn.id = Usr.region_id)
            LEFT JOIN states as Sts ON (Sts.id = Usr.state_id)
            LEFT JOIN cities as Cts ON (Cts.id = Usr.city_id)
            LEFT JOIN branch as Brch ON (Brch.id = Usr.branch_id)
            LEFT JOIN users as EnrolledBy ON (EnrolledBy.id = Usr.enrolled_by) WHERE Usr.id = ".$user_id."";
            $data = Yii::$app->db->createCommand($userdetails)->queryOne();   
            return $data;
    }

    public function DownloadEnrolledUsers($bsm_user_id,$program_id,$status='',$territory='',$state='',$city='',$branch='',$start_date='',$end_date=''){
        $region = $branch_id =  $city_id =  $state_id = $dateFilter = '';
        if(!empty($status)){

            $enrollment_status =" AND Usr.enrollment_status =".$status."";
        }else{
            $enrollment_status = " AND Usr.enrollment_status IN (1,2,3)";
        }
        if(!empty($territory)){
            $region = " AND Usr.region_id =".$territory."";
        }
        if(!empty($state)){
            $state_id = " AND Usr.state_id =".$state."";
        }
        if(!empty($city)){
            $city_id = " AND Usr.city_id =".$city."";
        }
        if(!empty($branch)){
            $branch_id = " AND Usr.branch_id =".$branch."";
        }
        if(!empty($start_date) && !empty($end_date)){
            $dateFilter = " AND Usr.enrollment_date BETWEEN '".$start_date."' and '".$end_date."' ";
        }
        $userdetails = "SELECT Usr.id as user_id,Usr.region_id,Usr.state_id,Usr.city_id,Usr.branch_id,Usr.username as mobile_number,Usr.first_name,Usr.last_name,
            Rgn.region_name,Sts.state_name,Cts.city_name,Usr.enrollment_date,Usr.is_enrolled,Usr.enrolled_by as enrolled_by_user_id,EnrolledBy.first_name as enrolled_by,
            Usr.reject_count,Usr.company_name as firm_name,Usr.rejection_reason,Usr.id_number as unique_id_number,
            CASE WHEN Usr.enrollment_status = 0 THEN 'Pending'
            WHEN Usr.enrollment_status = 1 THEN 'Enrolled'
            WHEN Usr.enrollment_status = 2 THEN 'Approved'
            WHEN Usr.enrollment_status = 3 THEN 'Rejected'
            ELSE 'Unknown Status' END AS enrollment_status, Usr.dob,Usr.anniversary_date,Usr.email_id,
            Files.id_card as unique_id_photo,Files.visiting_card,Brch.branch,Usr.address,Usr.pincode,
            Usr.billing_address,Usr.shipping_address,
            CASE WHEN Usr.member_type_id=2 THEN 'Solitaire'
            WHEN Usr.member_type_id=1 THEN 'Bespoke'
            ELSE 'not set' END as aid_type,Usr.member_type_id,Usr.gender as gender_id,
            CASE WHEN Usr.gender =1 THEN 'Male'
            WHEN Usr.gender = 2 THEN 'Female'
            WHEN Usr.gender = 3 THEN 'Other'
            ELSE 'Not Set' END as gender,Usr.whatsapp_number,Usr.alternative_number,
            CASE WHEN Usr.unique_id_type= 1 THEN 'Adhaar CARD'
            WHEN Usr.unique_id_type= 2 THEN 'PAN Card'
            WHEN Usr.unique_id_type= 3 THEN 'Voters ID Card'
            WHEN Usr.unique_id_type= 4  THEN 'Driving License'
            ELSE 'Not Set' END AS unique_id_type,Usr.unique_id_type as unique_id_type_id,
            Usr.area,Usr.user_detail_name as display_name, ASM.user_detail_name as asm_name,
            ASM.username as asm_number, SE.user_detail_name as se_name, SE.username as se_mobile_number 
            FROM users as Usr
            LEFT JOIN user_enrollment_files as Files ON (Files.user_id = Usr.id)
            LEFT JOIN regions as Rgn ON (Rgn.id = Usr.region_id)
            LEFT JOIN states as Sts ON (Sts.id = Usr.state_id)
            LEFT JOIN cities as Cts ON (Cts.id = Usr.city_id)
            LEFT JOIN branch as Brch ON (Brch.id = Usr.branch_id)
            LEFT JOIN users as EnrolledBy ON (EnrolledBy.id = Usr.enrolled_by)
            LEFT JOIN users as ASM ON (ASM.id = Usr.asm_user_id)
            LEFT JOIN users as SE ON(SE.id = Usr.se_user_id) WHERE Usr.program_id = ".$program_id." AND Usr.bsm_user_id = ".$bsm_user_id." 
            $state_id $enrollment_status $region $city_id $branch_id $dateFilter order by Usr.id DESC";
            // echo $userdetails;exit;
            $data = Yii::$app->db->createCommand($userdetails)->queryAll();   
            return $data;
    }

}


