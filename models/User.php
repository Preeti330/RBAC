<?php

namespace app\models;

use app\models\Task;
use app\models\TaskDetail;
use Firebase\JWT\JWT;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\rbac\Permission;
use yii\web\HttpException;
use yii\web\Request as WebRequest;
use yii\data\ActiveDataProvider;
use yii\data\SqlDataProvider;

/**
 * Class User
 *
 * @property integer $id
 * @property string $username
 //* @property integer $program_id
 * @property integer $user_role_id
 * @property string $auth_key
 * @property integer $access_token_expired_at
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property integer $last_login_at
 * @property string $last_login_ip
 * @property boolean $status
 * @property integer $role
 * @property integer $created_date
 * @property integer $updated_date
 * @property integer $campaign_id
 * @property string $department
 * @property string $company
 * @property string $phone
 * @property string $address
 * @property integer $supervisor
 * @property integer $is_verified
 * @property integer $points
 * @property string $default_lang
 * @property string $firstname
 * @property string $lastname
 * @property integer $region_id
 * @property string $profile_img
 * @property string $wallet
 * @property string $generated_super
 * @property integer $is_common_terms
 * @property integer $source_from
 * @package app\models
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{

    const ROLE_SUPERADMIN = 1;
    const ROLE_ADMIN = 2;
    const ROLE_HUBADMIN = 8;
    const ROLE_REPORTADMIN = 9;

    const ROLE_BRANCHMANAGER = 3;
    //  const ROLE_TEAMLEADER = 8;
    const ROLE_AGENT = 9;
    //  const ROLE_AGENCYTEAMLEADER = 10;
    const ROLE_AGENCYAGENT = 11;
    const ROLE_USER = 12;
    const ROLE_STAFF = 6;
    const ROLE_DASHBOARDADMIN = 16;

    const ROLE_REGIONUSER = 18;
    const ROLE_AGENTCALLCENTER = 13;
    const ROLE_MYSTERYAGENT = 17;
    const ROLE_NT = 500;
    const ROLE_UH = 12;
    const ROLE_SH = 50;
    const ROLE_RSH = 30;
    const ROLE_SM = 60;
    const ROLE_ASM = 70;
    const ROLE_SE = 90;
    const ROLE_MDM = 100;
    const ROLE_RETAILER = 200;
    const STATUS_DELETED = -10;
    const STATUS_DISABLED = 0;
    const STATUS_PENDING = -1;
    const STATUS_ACTIVE = 1;
    const ROLE_M1 = 4;
    const ROLE_REWARDUSER = 22;

    /** @var  string to store JSON web token */
    public $access_token;

    /** @var  array $permissions to store list of permissions */
    public $permissions;

    /**
     * @inheritdoc
     */

    public static function tableName()
    {
        return 'users';
    }

    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'created_at' => Yii::t('app', 'Registration time'),
        ];
    }

    /** @inheritdoc */
    public function behaviors()
    {
        // TimestampBehavior also provides a method named touch() that allows you to assign the current timestamp to the specified attribute(s) and save them to the database. For example,
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_date',
                'updatedAtAttribute' => 'updated_date',
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    // explicitly list every field, best used when you want to make sure the changes
    // in your DB table or model attributes do not cause your field changes (to keep API backward compatibility).
    public function fields()
    {
        $fields = [
            'id',
            'user_role_id',
            'user_role_name' => function ($model) {
                return isset($model->userRole->role_name) ? $model->userRole->role_name : null;
            },
            //'program_id',
            'username',
            'password_hash',
            'last_login_at',
            'last_login_ip',
            'status',
            'status_label' => function () {
                $statusLabel = '';
                switch ($this->status) {
                    case self::STATUS_ACTIVE:
                        $statusLabel = Yii::t('app', 'Active');
                        break;
                    case self::STATUS_PENDING:
                        $statusLabel = Yii::t('app', 'Waiting Confirmation');
                        break;
                    case self::STATUS_DISABLED:
                        $statusLabel = Yii::t('app', 'Disabled');
                        break;
                    case self::STATUS_DELETED:
                        $statusLabel = Yii::t('app', 'Deleted');
                        break;
                }
                return $statusLabel;
            },
            'created_date',
            'updated_date',
            'supervisor',
            'supervisor_name' => function ($model) {
                return isset($model->userDetails->user_detail_name) ? $model->userDetails->user_detail_name : null;
            },
            'supervisor_number' => function ($model) {
                return isset($model->user->username) ? $model->user->username : null;
            },
            'is_verified',
            'points',
            'default_lang',
            'wallet',
            'generated_super',
        ];

        // // If role is staff and admin, then return permissions  
        // if ($this->user_role_id == 8 || $this->user_role_id == 9) {
        //     $fields['permissions'] = function () {
        //         $authManager = Yii::$app->authManager;
        //         /** @var Permission[] $availablePermissions */
        //         $availablePermissions = $authManager->getPermissions();
        //         /** @var array $tmpPermissions to store permissions assigned to the staff*/
        //         $tmpPermissions = [];
        //         /** @var Permission[] $userPermissions */
        //         $userPermissions = $authManager->getPermissionsByUser($this->getId());
        //         if (!empty($availablePermissions)) {

        //             /**
        //              * @var string $permissionKey
        //              * @var Permission $permission
        //              */
        //             foreach ($availablePermissions as $permissionKey => $permission) {
        //                 $tmpPermission = [
        //                     'name' => $permission->name,
        //                     'description'  => $permission->description,
        //                     'checked' => false,
        //                 ];
        //                 if (!empty($userPermissions)) {
        //                     foreach ($userPermissions as $userPermissionKey => $userPermission) {

        //                         if ($userPermission->name == $permission->name) {

        //                             $tmpPermission['checked'] = true;

        //                             break;
        //                         }
        //                     }
        //                 }
        //                 $tmpPermissions[] = $tmpPermission;
        //             }
        //         }
        //         return $tmpPermissions;
        //     };
        // }


        return $fields;
    }

    public function getUserRole()
    {
        return $this->hasOne(UserRole::className(), ['id' => 'user_role_id']);
    }

    public function getUserDetails()
    {
        return $this->hasOne(UserDetails::className(), ['user_id' => 'supervisor']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'supervisor']);
    }

    private function getRoleName()
    {
        $roleName = '';
        switch ($this->role) {
            case self::ROLE_USER:
                $roleName = 'menuadmin';
                break;
            case self::ROLE_STAFF:
                $roleName = 'staff';
                break;
            case self::ROLE_ADMIN:
                $roleName = 'admin';
                break;
            case self::ROLE_SUPERADMIN:
                $roleName = 'superadmin';
                break;
            case self::ROLE_HUBADMIN:
                $roleName = 'hubadmin';
                break;
            case self::ROLE_MYSTERYAGENT:
                $roleName = 'mysteryagent';
                break;

            case self::ROLE_BRANCHMANAGER:
                    $roleName = 'branchmanager';
                    break;  
            case self::ROLE_REPORTADMIN:
                        $roleName = 'reportadmin';
                        break;  
        }
        return $roleName;
    }

    private function getRoleLabel()
    {
        $roleLabel = '';
        switch ($this->role) {
            case self::ROLE_USER:
                $roleLabel = Yii::t('app', 'Agent');
                break;
            case self::ROLE_STAFF:
                $roleLabel = Yii::t('app', 'Team Leader');
                break;
            case self::ROLE_ADMIN:
                $roleLabel = Yii::t('app', 'Administrator');
                break;
            case self::ROLE_SUPERADMIN:
                $roleLabel = Yii::t('app', 'Super Admin');
                break;
            case self::ROLE_HUBADMIN:
                $roleLabel = Yii::t('app', 'Regional Administrator');
                break;
            case self::ROLE_MYSTERYAGENT:
                $roleLabel = Yii::t('app', 'Mystery QC Admin');
                break;

            case self::ROLE_BRANCHMANAGER:
                    $roleLabel = Yii::t('app', 'Branch Manager');
                    break; 
                    
            case self::ROLE_REPORTADMIN:
                   $roleLabel = Yii::t('app', 'Report Admin');
                    break;    
                      

        }

        return $roleLabel;
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'string', 'length' => [3, 50]],
            // [['firstname', 'lastname', 'default_lang', 'generated_super','profile_img'], 'string'],
            //   ['username', 'match', 'pattern' => '/^[A-Za-z0-9_-]{3,15}$/', 'message' => Yii::t('app', 'Your username can only contain alphanumeric characters, underscores and dashes.')],
            //  ['username', 'validateUsername'],
            // ['email', 'trim'],
            //['email', 'required'],
            // ['email', 'email'],
            // ['email', 'string', 'max' => 255],
            // ['email', 'validateEmail'],
            ['password', 'string', 'min' => 6],
            ['password', 'validatePasswordSubmit'],
            [['last_login_at'], 'date', 'format' => 'yyyy-M-d H:m:s'],
            [['last_login_ip'], 'ip'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DISABLED]],

            [['user_role_id', 'program_id', 'supervisor'], 'integer'],
            /*  ['role', 'in', 'range' => [self::ROLE_SE, self::ROLE_UH, self::ROLE_ADMIN,self::ROLE_SUPERADMIN,
            self::ROLE_MDM, self::ROLE_RETAILER]],*/

           //   ['permissions', 'validatePermissions'],
            //[['access_token', 'permissions'], 'safe'],
         
        ];
    }

    public function addPoints($points, $desc, $creditUser = null)
    {
        $up = new UserPoints();
        $up->user_id = $this->id;
        $up->points = $points;
        $up->description = $desc;
        $up->status = 1;
        $up->campaign_id = 1;
        if (isset($creditUser)) {
            $up->credit_user = $creditUser;
            $up->status = 0;
        }
        if ($up->save(false)) {
            if ($up->status === 1) {
                $this->points = $this->points + $points;
                $this->save(false);
            }
        }
        return;
    }

    public function addWallet($amt, $desc)
    {
        $wallet = new Wallet();
        $wallet->user_id = $this->id;
        $wallet->amount = $amt;
        $wallet->description = $desc;
        $wallet->status = 1;
        $wallet->campaign_id = 1;
        $wallet->save(false);
        if ($wallet->save(false)) {
            $this->wallet = $this->wallet + $amt;
            $this->save(false);
        }
    }

    public function createTasks()
    {
        date_default_timezone_set('Asia/Kolkata');
        $tasksList = Task::find()->where(['campaign_id' => $this->campaign_id, 'status' => 1, 'user_role' => $this->role])->all();
        //throw new HttpException(422, json_encode('At Create Tasks - ' . count($tasksList) . ' - ' . $this->campaign_id . ' - ' . $this->role));
        foreach ($tasksList as $task) {
            $datesList = $task->timeslotdates;
            //throw new HttpException(422, json_encode('At Create Tasks - TasksList'));
            foreach ($datesList as $taskDate) {
                //throw new HttpException(422, json_encode('The task count: ' . $taskCount));
                $taskdet = new TaskDetail();
                $taskdet->task_id = $task->id;
                $taskdet->task_date = $taskDate;
                $taskdet->status = 0;
                $taskdet->user_id = $this->id;
                $taskdet->success_points = $task->success_points;
                $taskdet->task_question_id = $task->task_question_id;
                $taskdet->subtask_question_id = $task->subtask_question_id;
                $taskdet->save(false);
                //throw new HttpException(422, json_encode('The exception is: ' . $taskdet->getErrors()));
            }
        }
    }

    public function createAddlTasks($id)
    {
        date_default_timezone_set('Asia/Kolkata');
        $task = Task::findOne($id);
        //throw new HttpException(422, json_encode('At Create Tasks - ' . count($tasksList) . ' - ' . $this->campaign_id . ' - ' . $this->role));
        $datesList = $task->timeslotdates;
        //throw new HttpException(422, json_encode('At Create Tasks - TasksList'));
        foreach ($datesList as $taskDate) {
            //throw new HttpException(422, json_encode('The task count: ' . $taskCount));
            $taskdet = new TaskDetail();
            $taskdet->task_id = $task->id;
            $taskdet->task_date = $taskDate;
            $taskdet->status = 0;
            $taskdet->user_id = $this->id;
            $taskdet->success_points = $task->success_points;
            $taskdet->task_question_id = $task->task_question_id;
            $taskdet->subtask_question_id = $task->subtask_question_id;
            $taskdet->save(false);
            //throw new HttpException(422, json_encode('The exception is: ' . $taskdet->getErrors()));
        }
    }

    public function triggerMDPoints()
    {
        $pointsList = UserPoints::find()->where(['credit_user' => $this->id, 'status' => 0])->all();
        foreach ($pointsList as $pl) {
            $pl->status = 1;
            $pl->save(false);
            $md = User::findOne($pl->user_id);
            $md->points += $pl->points;
            $md->save(false);
        }
    }

    public function getSupervisorPhone()
    {
        $supervisor = User::findOne($this->supervisor);
        if (isset($supervisor)) {
            return $supervisor->username;
        }

        return '';
    }

    public function validatePasswordSubmit($attribute, $params)
    {
        // get post type - POST or PUT
        $request = Yii::$app->request;

        // if POST, mode is create
        if ($request->isPost) {
            if ($this->$attribute == '') {
                $this->addError($attribute, Yii::t('app', 'The password is required.'));
            }
        } elseif ($request->isPut) {
            // No action required
        }
    }

    /**
     * Validate permissions array
     *
     * @param $attribute
     * @param $params
     */
    public function validatePermissions($attribute, $params)
    {

        if (!empty($this->$attribute)) {

            $authManager = Yii::$app->authManager;
            // Get existing permissions
            $existingPermissions = $authManager->getPermissions();
           

            // Loop attributes
            foreach ($this->$attribute as $permissionKey => $permission) {
                // Validate attributes in the array
                if (
                    array_key_exists('name', $permission) === false ||
                    array_key_exists('description', $permission) === false ||
                    array_key_exists('checked', $permission) === false
                ) {
                    $this->addError($attribute, Yii::t('app', 'The permission is not valid format.'));
                }
                // Validate name
                elseif (isset($existingPermissions[$permission['name']]) == false) {
                    $this->addError($attribute, Yii::t('app', 'The permission name \'' . $permission['name'] . '\' is not valid.'));
                }
                // Validate checked
                elseif (is_bool($permission['checked']) === false) {
                    $this->addError($attribute, Yii::t('app', 'The permission checked \'' . $permission['checked'] . '\' is not valid.'));
                }
            }
        }
    }

    public function setName($name)
    {
        $names = mb_split(' ', $name, 3);
        $len = count($names);
        if ($len > 1) {
            $this->firstname = trim($names[0]);
            $this->lastname = trim($names[$len - 1]);
        } elseif ($len > 0) {
            $this->firstname = trim($names[0]);
        }
        return;
    }

    public function getName()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
    /**
     * Validate username
     *
     * @param $attribute
     * @param $params
     */
    public function validateUsername($attribute, $params)
    {
        // get post type - POST or PUT
        $request = Yii::$app->request;

        // if POST, mode is create
        if ($request->isPost) {
            // check username is already taken

            $existingUser = User::find()
                ->where(['username' => $this->$attribute])
                ->count();
            if ($existingUser > 0) {
                $this->addError($attribute, Yii::t('app', 'The username has already been taken.'));
            }
        } elseif ($request->isPut) {
            // get current user
            $user = User::findIdentityWithoutValidation($this->id);
            if ($user == null) {
                $this->addError($attribute, Yii::t('app', 'The system cannot find requested user.'));
            } else {
                // check username is already taken except own username
                $existingUser = User::find()
                    ->where(['=', 'username', $this->$attribute])
                    ->andWhere(['!=', 'id', $this->id])
                    ->count();
                if ($existingUser > 0) {
                    $this->addError($attribute, Yii::t('app', 'The username has already been taken.'));
                }
            }
        } else {
            // unknown request
            $this->addError($attribute, Yii::t('app', 'Unknown request'));
        }
    }

    /**
     * Validate email
     *
     * @param $attribute
     * @param $params
     */
    public function validateEmail($attribute, $params)
    {
        // get post type - POST or PUT
        $request = Yii::$app->request;

        // if POST, mode is create
        if ($request->isPost) {
            // check username is already taken

            $existingUser = User::find()
                ->where(['email' => $this->$attribute])
                ->count();

            if ($existingUser > 0) {
                $this->addError($attribute, Yii::t('app', 'The email has already been taken.'));
            }
        } elseif ($request->isPut) {
            // get current user
            $user = User::findIdentityWithoutValidation($this->id);

            if ($user == null) {
                $this->addError($attribute, Yii::t('app', 'The system cannot find requested user.'));
            } else {
                // check username is already taken except own username
                $existingUser = User::find()
                    ->where(['=', 'email', $this->$attribute])
                    ->andWhere(['!=', 'id', $this->id])
                    ->count();
                if ($existingUser > 0) {
                    $this->addError($attribute, Yii::t('app', 'The email has already been taken.'));
                }
            }
        } else {
            // unknown request
            $this->addError($attribute, Yii::t('app', 'Unknown request'));
        }
    }

    /**
     * @return bool Whether the user is confirmed or not.
     */
    public function getIsConfirmed()
    {
        return $this->confirmed_at != null;
    }

    /**
     * @return bool Whether the user is blocked or not.
     */
    public function getIsBlocked()
    {

        return $this->blocked_at != null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $user = static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
        if (
            $user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)
        ) {
            return null;
        }
        return $user;
    }

    public static function findIdentityWithoutValidation($id)
    {
        $user = static::findOne(['id' => $id]);

        return $user;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user = static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        if (
            $user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)
        ) {
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
    public static function findByUsernameWithRoles($username, $roles)
    {

        /** @var User $user */
        $user = static::find()->where([
            'username' => $username,
            'status' => self::STATUS_ACTIVE,

        ])->andWhere(['in', 'user_role_id', $roles])->one();

        if (
            $user !== null &&
            ($user->getIsConfirmed() == false)
        ) {
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
    public static function findByPasswordResetToken($token)
    {
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
    public static function isPasswordResetTokenValid($token)
    {
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
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * Confirm Email address
     *      Not implemented Yet
     *
     * @return bool whether the email is confirmed o not
     */
    /*public function confirmEmail() {
    if($this->unconfirmed_email != '') {
    $this->email = $this->unconfirmed_email;
    }
    $this->registration_ip = Yii::$app->request->userIP;
    $this->status = self::STATUS_ACTIVE;
    $this->save(false);
    $this->touch('confirmed_at');

    return true;
    }*/

    /**
     * Generate access token
     *  This function will be called every on request to refresh access token.
     *
     * @param bool $forceRegenerate whether regenerate access token even if not expired
     *
     * @return bool whether the access token is generated or not
     */
    public function generateAccessTokenAfterUpdatingClientInfo($forceRegenerate = false)
    {
        // update client login, ip
        $this->last_login_ip = Yii::$app->request->userIP;
        $this->last_login_at = new Expression('NOW()');

        // check time is expired or not
        if (
            $forceRegenerate == true
            || $this->access_token_expired_at == null
            || (time() > strtotime($this->access_token_expired_at))
        ) {
            // generate access token
            $this->generateAccessToken();
        }
        $this->save(false);
        return true;
    }

    public function generateAccessToken()
    {
        // generate access token
        //        $this->access_token = Yii::$app->security->generateRandomString();
        $tokens = $this->getJWT();
        $this->access_token = $tokens[0]; // Token
        $this->access_token_expired_at = date('Y-m-d H:i:s', $tokens[1]['exp']); // Expire
    }

    public function beforeSave($insert)
    {
        // Convert username to lower case
        $this->username = strtolower($this->username);
        

        // Fill unconfirmed email field with email if empty
        /*if($this->unconfirmed_email == '') {
        $this->unconfirmed_email = $this->email;
        }*/

        // Fill registration ip with current ip address if empty
        /*if($this->registration_ip == '') {
        $this->registration_ip = Yii::$app->request->userIP;
        }*/

        // Fill auth key if empty
        if ($this->auth_key == '') {
            $this->generateAuthKey();
        }

        return parent::beforeSave($insert);
    }

    /*public function afterSave($insert, $changedAttributes)
    {
    if ($insert) {
    if ($this->role === 200) $this->createTasks();
    }
    // ---- Start to process permissions
    return parent::afterSave($insert, $changedAttributes);

    }*/

    public function getPassword()
    {
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

    protected static function getSecretKey()
    {
        return Yii::$app->params['jwtSecretCode'];
    }

    // And this one if you wish
    protected static function getHeaderToken()
    {

        return [];
    }

    /**
     * Logins user by given JWT encoded string. If string is correctly decoded
     * - array (token) must contain 'jti' param - the id of existing user
     * @param  string $accessToken access token to decode
     * @return mixed|null          User model or null if there's no user
     * @throws \yii\web\ForbiddenHttpException if anything went wrong
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {

     
        //  $secret = static::getSecretKey();
        // // Decode token and transform it into array.
        // // Firebase\JWT\JWT throws exception if token can not be decoded


        // try {
        //     $decoded = JWT::decode($token, $secret, [static::getAlgo()]);
        // } catch (\Exception $e) {
        //     return false;
        // }
        // static::$decodedToken = (array) $decoded;
        // // If there's no jti param - exception
        // if (!isset(static::$decodedToken['jti'])) {
        //     return false;
        // }
        // // JTI is unique identifier of user.
        // // For more details: https://tools.ietf.org/html/rfc7519#section-4.1.7
        // $id = static::$decodedToken['jti'];


//         // return static::findByJTI($id);

        return static::findOne(['device_token' => $token]);

       // $token = \Yii::$app->jwt->getParser()->parse((string) $token); // Parses from a string
        // $signer = $this->getAlgo();

        // $data = \Yii::$app->jwt->getValidationData(); // It will use the current time to validate (iat, nbf and exp)
        // $data->setIssuer('http://myurl');
        // $data->setAudience('http://myurl');
        // $data->setId('myid');

        // $username = $data->getClaim('username');

        // if ($token->validate($data) && $token->verify($signer, 'testing')) {
        //     return static::findByUsername($username);
        // }

        // return null;

    }

    /**
     * Finds User model using static method findOne
     * Override this method in model if you need to complicate id-management
     * @param  string $id if of user to search
     * @return mixed       User model
     */
    public static function findByJTI($id)
    {
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

        if (
            $user !== null &&
            ($user->getIsBlocked() == true || $user->getIsConfirmed() == false)
        ) {
            return null;
        }

        return $user;
    }

    /**
     * Getter for encryption algorytm used in JWT generation and decoding
     * Override this method to set up other algorytm.
     * @return string needed algorytm
     */
    public static function getAlgo()
    {
        return 'HS256';
    }

    /**
     * Returns some 'id' to encode to token. By default is current model id.
     * If you override this method, be sure that findByJTI is updated too
     * @return integer any unique integer identifier of user
     */
    public function getJTI()
    {

        return $this->getId();
    }

    /**
     * Encodes model data to create custom JWT with model.id set in it
     * @return array encoded JWT
     */
    public function getJWT()
    {

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
                //  'program_id' => $this->program_id,
            ],
        ], static::getHeaderToken());

        // Set up id

        $token['jti'] = $this->getJTI(); // JSON Token ID: A unique string, could be used to validate a token, but goes against not having a centralized issuer authority.
        return [JWT::encode($token, $secret, static::getAlgo()), $token];
    }

    public function getPendingActivitesData($program_id, $campaign_id = '', $user_id = '', $region_id = '')
    {

        $where = '';
        if ($campaign_id != 0) {
            $where = 'AND campaign_id = ' . $campaign_id;
        }

        $sql = "select Users.program_id,
                           Users.region_id,
                           Users.username AS mobilenumber,
                           Users.email,
                           Users.firstname,
                           Users.is_independant_user,
                           Users.is_verified,
                           CASE WHEN Users.is_verified = TRUE THEN 'Active'
                                ELSE 'In Active' END AS Status,
                           Users.created_date,
                           UserStore.store_name,
                           UserStore.store_img1,
                           UserStore.store_img2,
                           UserStore.store_address,
                           UserStore.store_contactno,
                           UserStore.id AS store_id
                    FROM users as Users
                    JOIN user_stores as UserStore on UserStore.user_id = Users.id
                    WHERE Users.is_independant_user = FALSE AND
                          Users.program_id = " . $program_id . " AND Users.supervisor = " . $user_id . "
                          $where";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        if (isset($data) && !empty($data)) {
            return $data;
        } else {
            return 0;
        }
    }

    public function getOutletEnrolmentCount($programId, $userId, $campaignId = '')
    {
        $today = date('Y-m-d');
        $lastWeek = date('Y-m-d', strtotime($today . '-1 week'));

        $sql = "SELECT (
                    SELECT COUNT(*)
                    FROM enrollhistory
                    WHERE mdm_id = " . $userId . " AND program_id = " . $programId . ") AS total_enrollment,
                    (
                    SELECT COUNT(*)
                    FROM visithistory
                    WHERE mdm_id = " . $userId . " AND program_id = " . $programId . ") AS total_visits,
                    (
                    SELECT COUNT(*)
                    FROM enrollhistory
                    WHERE mdm_id = " . $userId . " AND created_date:: DATE = '" . $today . "' AND program_id = " . $programId . ") AS today_enrollment,
                    (
                    SELECT COUNT(*)
                    FROM visithistory
                    WHERE mdm_id = " . $userId . " AND created_date:: DATE = '" . $today . "' AND program_id = " . $programId . ") AS today_visits,
                    (
                    SELECT COUNT(*)
                    FROM enrollhistory
                    WHERE mdm_id = " . $userId . " AND created_date:: DATE BETWEEN '" . $lastWeek . "' AND '" . $today . "' AND program_id = " . $programId . ") AS week_enrollment,
                    (
                    SELECT COUNT(*)
                    FROM visithistory
                    WHERE mdm_id = " . $userId . " AND created_date:: DATE BETWEEN '" . $lastWeek . "' AND '" . $today . "' AND program_id = " . $programId . ") AS week_visits";
        $data = Yii::$app->db->createCommand($sql)->queryOne();
        return $data;
    }

    public function updatePoints($user_id = '', $points = '')
    {
        $sql = "update users set points = points + " . $points . " where id = " . $user_id;
        $query = Yii::$app->db->createCommand($sql)->execute();
    }

    public function getPendingVisitsData($programId, $campaignId = '', $userId = '', $regionId = '')
    {
        $where = '';
        if ($campaignId != 0) {
            $where = " AND Users.campaign_id = '" . $campaignId . "'";
        }

        $today = date('Y-m-d');

        $sql = "SELECT Users.program_id,
                        Users.region_id,
                        Users.username AS mobilenumber,
                        Users.email,
                        Users.firstname,
                        Users.is_verified, CASE WHEN Users.is_verified = TRUE THEN 'Active' ELSE 'Inactive' END AS STATUS,
                        Users.created_date,
                        UserStore.store_name,
                        UserStore.store_img1,
                        UserStore.store_img2,
                        UserStore.store_address,
                        UserStore.store_contactno,
                        UserStore.id AS store_id,
                        CASE WHEN (Users.id NOT IN (SELECT retailer_id FROM visithistory WHERE mdm_id = " . $userId . " AND program_id = " . $programId . ")) THEN FALSE ELSE TRUE END AS is_outlet_visited
                    FROM users AS Users
                    JOIN user_stores AS UserStore ON UserStore.user_id = Users.id
                    WHERE Users.is_verified = TRUE AND Users.program_id = " . $programId . " AND Users.created_date::DATE = '" . $today . "'
                    AND Users.supervisor = " . $userId . $where;
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        if (isset($data) && !empty($data)) {
            return $data;
        } else {
            return 0;
        }
    }

    public function getTotalActivationCount($programId, $campaignId = '', $userId = '', $regionId = '')
    {
        $where = '';
        if ($campaignId != 0) {
            $where = " AND Users.campaign_id = '" . $campaignId . "'";
        }

        $today = date('Y-m-d');

        $sql = "SELECT count(*) AS totalActivated FROM visithistory WHERE mdm_id = " . $userId . " AND program_id = " . $programId;
        $data = Yii::$app->db->createCommand($sql)->queryOne();
        return $data;
    }

    // public function getUsersDetails($value = '')
    // {
    //     $sqlQuery = "
    //                         SELECT
    //                         Users.id,
    //                         UserDetail.user_detail_name AS username,
    //                         Users.user_role_id
    //                         FROM users AS Users
    //                         JOIN userdetails AS UserDetail ON UserDetail.user_id = Users.id
    //                         WHERE  Users.user_role_id = 9
    //                         AND Users.status = 1
    //                         ORDER BY Users.id
    //                     ";
    //     $data = Yii::$app->db->createCommand($sqlQuery)->queryAll();
    //     return $data;
    // }

    public function getLastUserId()
    {
        $sql = "SELECT id FROM users ORDER BY id DESC LIMIT 1";
        $data = Yii::$app->db->createCommand($sql)->queryOne();
        return ($data) ? $data['id'] : 0;
    }

    public function checkUserMobile($mobile)
    {
        $sql = "SELECT * FROM users WHERE username = '" . $mobile . "'";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return ($data) ? 1 : 0;
    }

    public function getUserIdByOsmosys($dummy_rocode)
    {
        $sql = "SELECT id FROM users WHERE dummy_rocode::int in (" . $dummy_rocode . ") LIMIT 1";
        $data = Yii::$app->db->createCommand($sql)->queryOne();
        return ($data) ? $data['id'] : 0;
    }

    public function getUserIdByMobileNumber($username)
    {
        $sql = "SELECT id FROM users WHERE username in ('" . $username . "') LIMIT 1";
        $data = Yii::$app->db->createCommand($sql)->queryOne();
        return ($data) ? $data['id'] : 0;
    }

    public function getUsersByRoleId($role_id)
    {
        if (empty($role_id)) {
            $role_id = 4;
        }
        $sql = "SELECT
                Users.id as user_id, Users.username as mobile, Users.user_role_id, UserDetails.state_code, States.region_id, UserDetails.user_detail_name
                from userdetails UserDetails
                JOIN users Users ON Users.id= UserDetails.user_id
                LEFT JOIN states States ON States.state_code = UserDetails.state_code AND States.state_year=2021
                LEFT JOIN regions Regions ON Regions.id = States.region_id
                where Users.user_role_id=" . $role_id . " and Users.status=1   ";

        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    public function getUserDetailsByRocode($rocode)
    {
        $whereDummyrocode = "";
        if (isset($rocode) && !empty($rocode)) {
            $whereDummyrocode = " AND Users.dummy_rocode::int in ( " . $rocode . " ) ";
        }
        $sql = "SELECT Users.dummy_rocode, Users.id as user_id, UserDetails.state_code, States.state_name, States.region_id, Regions.region_name FROM users Users
                JOIN userdetails UserDetails ON UserDetails.user_id=Users.id
                LEFT JOIN states States ON States.state_code = UserDetails.state_code AND States.state_year=2021
                LEFT JOIN regions Regions ON Regions.id = States.region_id
                LEFT JOIN channels Channels ON Channels.id = UserDetails.channel_id
                WHERE Users.user_role_id=5
                " . $whereDummyrocode . "
                ORDER BY 1 LIMIT 1
                ";
        return Yii::$app->db->createCommand($sql)->queryOne();
    }

    public function getGapanalysisreport($month, $year, $state, $region)
    {
        $sql = "SELECT gd.*, c.channel_code, c.channel_desc, s.state_code, s.state_name, r.region_name
                FROM gap_analysis_data AS gd 
                LEFT JOIN channels AS c ON c.id = gd.channel_id
                LEFT JOIN states AS s ON s.state_code = gd.state_code
                LEFT JOIN regions AS r ON r.id = gd.region_id
                WHERE gd.gap_analysis_month = " . $month . " AND gd.gap_analysis_year = " . $year;
        if (isset($state) && !empty($state)) {
            $sql .= " AND gd.state_code = " . $state;
        }

        if (isset($region) && !empty($region)) {
            $sql .= " AND gd.region_id = " . $region;
        }
        $sql .= " ORDER BY gd.id DESC";

        $data = Yii::$app->db->createCommand($sql)->queryAll();
        return $data;
    }
}
