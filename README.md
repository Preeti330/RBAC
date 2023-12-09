# RBAC
Access Permission By Token , validating user based on token and role assigining permission for user

yii2 to uses access token to authonicate user. 
access token is sent by consumer from authoriztion server and sent to api server via bearer token.
To impletement Role Base Access Management (RBAC) , i have followed the bellow steps

RBAC helps to assigin the permision for the users like admin, manager,customer,etc using access token .

for validating every action we registred and allowing the user to access it happen in two process .
1.Authentication : in this process its verifying the identity of  user . it uses username,pwd or token to identify user based on the login .
                   while user get login, modal\User.php is implements with  \yii\web\IdentityInterface and it have some function like 
				   findIdentity($id),findByUsername($username),findIdentityByAccessToken to get identity of user ,
				   when user get login the identity of user get loads with help of web/User.php this file stores the user infomation throught the session.
				   we get the identity of user by ,
				   
					$identity = Yii::$app->user->identity;
					// the ID of the current user. `null` if the user not authenticated.
					$id = Yii::$app->user->id;
					// whether the current user is a guest (not authenticated)
					$isGuest = Yii::$app->user->isGuest;

2.Authorization : after authonication of user while accessing perticular actionMethods it check permision of loged in user.
                  Yii2 provides two authoriztion 1.Access Control Filter(AFC) and 2.RBAC (Role Base Access Control)
				 
For every action , yii2 creates action_id and get its url form identity class eaxample : "v1/admin/displaysum " 
and then after it validate as before action and after action .
where in before action its first validate the perticular action is exsits and then its loads the roules form the behavior sections of controller class , its checks with identity of user .
if the perticular user have the persimison then its allow user to to do somethimg else its throw the error 			 
				  
AuthManger which helps to impletement RBAC , here i am loading the role form the database , so my data base should have the below mentioned 4 tables . 

befre that configrure the authManger in config/web.php

STEP 1 : use rbacDBManger in composer.json in require section 
       "require": {
        "php": ">=5.4.0",
        "yiisoft/yii2": "~2.0.5",
        "yiisoft/yii2-bootstrap": "~2.0.0",
        "firebase/php-jwt": "^4.0",
        "odannyc/yii2-sse": "^0.1.0",
        "guzzlehttp/guzzle": "^6.3",
        "yii2mod/yii2-rbac": "*"
    },
	
STEP 2 : configure authManager under components as (web.php)
			 'authManager' => [
									'class' => 'yii\rbac\DbManager',
									// 'cache' => 'cache',
									'defaultRoles' => ['guest'],
							  ],
							
		And add rbac Module under modules 
		'modules' => [
						'v1' => [
							'class' => 'app\modules\v1\Module',
						],
						'rbac' => [
							'class' => 'yii2mod\rbac\Module',
						],
					],
					
STEP 3 : migration are use to immpelement RBAC .
         create bellow four tables  (https://www.yiiframework.com/wiki/743/role-management) 
		 
		 create table `auth_rule`
								(
								`name` varchar(64) not null,
								`data` text,
								`created_at` integer,
								`updated_at` integer,
									primary key (`name`)
								);
		CREATE TABLE `auth_item` (
								`name` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',
								`type` INT(11) NOT NULL,
								`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
								`rule_name` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
								`data` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
								`created_at` INT(11) NULL DEFAULT NULL,
								`updated_at` INT(11) NULL DEFAULT NULL,
								PRIMARY KEY (`name`) USING BTREE,
								INDEX `rule_name` (`rule_name`) USING BTREE,
								INDEX `type` (`type`) USING BTREE,
								CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON UPDATE CASCADE ON DELETE SET NULL
						);


		CREATE TABLE `auth_item_child` (							
								`parent` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',						
								`child` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',						
								PRIMARY KEY (`parent`, `child`) USING BTREE,						
								INDEX `child` (`child`) USING BTREE,						
								CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON UPDATE CASCADE ON DELETE CASCADE,						
								CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON UPDATE CASCADE ON DELETE CASCADE						
							);	

       CREATE TABLE `auth_assignment` (					
								`item_name` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',				
								`user_id` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',				
								`created_at` INT(11) NULL DEFAULT NULL,				
								PRIMARY KEY (`item_name`, `user_id`) USING BTREE,				
								CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON UPDATE CASCADE ON DELETE CASCADE				
							);		


		CREATE TABLE `auth_item_child` (					
								`parent` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',				
								`child` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_general_ci',				
								PRIMARY KEY (`parent`, `child`) USING BTREE,				
								INDEX `child` (`child`) USING BTREE,				
								CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON UPDATE CASCADE ON DELETE CASCADE,				
								CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON UPDATE CASCADE ON DELETE CASCADE				
							);	

Step 5 : create Migration using  " php ./yii migrate/create init_rbac  "
		 in this migartion add permission for actionMethod and create role and assigin user to it  as bellow
		  public function safeUp()
				{
			 
						$auth = Yii::$app->authManager;

						// add "createPost" permission
						$Rbac = $auth->createPermission('Rbac');
						$Rbac->description = 'Rbac As post method';
						$auth->add($Rbac);

						// add "createGet" permission
						$Displaysum = $auth->createPermission('Displaysum');
						$Displaysum->description = 'Displaysum As get method';
						$auth->add($Displaysum);


						//create role
						$hubadmin = $auth->createRole('hubadmin');
						$auth->add($hubadmin);

						//add child-prenet config for / permison for Rbac 
						$auth->addChild($hubadmin, $Rbac);
						//assign user id for hubadmin
						$auth->assign($hubadmin, 301);

						//add child-prenet config for / permison for  Displaysum
						$auth->addChild($hubadmin, $Displaysum);

						//assign roles to users ,301 is user id
						$auth->assign($hubadmin,301);
				}
				 
            Run migration using " php yii migrate "
			
Step 7 : create controler as AdminController and here create two action as 
         actionRbac as post and actionDisplaysum as get method . 
		
Step 8 : in beforeaction its validate the user identity with permission for perticular api so , declare role for user here 
         add the action methods in behavior of access 
		 
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

					and in behavior['verb']

					$behaviors['verbs'] = [
					'class' => \yii\filters\VerbFilter::className(),
					'actions' => [
					'rbac'=>['post'],
					'displaysum'=>['get']

					]
					];
					
if roles are not metoned perticularly we get error as and also if other user try to access this methods , its throws the error as 

					"data": {
					"name": "Forbidden",
					"message": "You are not allowed to perform this action.",
					"code": 0,
					"status": 403,
					"type": "yii\\web\\ForbiddenHttpException"
					}
					
Step 9 : here i have overloaded User.php of findidentityByAccessToken to bellow bcz user was not able to identityfy based on access token so it was throwing the bad request.
  
