<?php
$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Calcutta',
    'components' => [
        'request' => [
            'cookieValidationKey' => 'K0I9yOJPLBqbaam4IWrqtelfxp1m1zEXB04f5H6D',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'authManager' => [
	        'class' => 'yii\rbac\DbManager',
            // 'cache' => 'cache',
            'defaultRoles' => ['guest'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                'ping'  =>  'site/ping',
               
                [
                    'class'         => 'yii\rest\UrlRule',
                    'controller'    => 'v1/admin',
                    'pluralize'     => false,
                    'tokens'        => [
                        '{id}'             => '<id:\d+>',
                    ],
                    'extraPatterns' => [
                       
                        'POST registration' => 'registration',
                        'OPTIONS registration' => 'options',

                        'POST login' => 'login',
                        'OPTIONS login' => 'options',

                        'POST rbac'=>'rbac',
                        'OPTIONS rbac'=>'options',

                        'GET displaysum'=>'displaysum',
                        'OPTIONS displaysum'=>'options',
                        
                        
                    ]
                ],
                [
					'class' => 'yii\rest\UrlRule',
					'controller' => 'v1/login',
					'pluralize' => false,
					'tokens' => [
						'{id}' => '<id:\d+>',
					],
					'extraPatterns' => [
						'OPTIONS {id}' => 'options',
                        
						'POST login' 		=> 'login',
						'OPTIONS login' 	=> 'options',
						'POST bsm-login' 	=> 'bsm-login',
						'OPTIONS bsm-login' => 'options',
						'POST otp-verification' => 'otp-verification',
						'OPTIONS otp-verification' => 'options',
						'POST resendotp'	=> 'resendotp',
						'OPTIONS resendotp' => 'options',
                        
                        'POST registration-upload'	=> 'registration-upload',
						'OPTIONS registration-upload' => 'options',
                        
					],
				],

                [
					'class' => 'yii\rest\UrlRule',
					'controller' => 'v1/test',
					'pluralize' => false,
					'tokens' => [
						'{id}' => '<id:\d+>',
					],
					'extraPatterns' => [
						'OPTIONS {id}' => 'options',
                        
						'POST login' 		=> 'login',
						'OPTIONS login' 	=> 'options',
						'POST bsm-login' 	=> 'bsm-login',
						'OPTIONS bsm-login' => 'options',
						'POST otp-verification' => 'otp-verification',
						'OPTIONS otp-verification' => 'options',
		
                        'POST registration-upload'	=> 'registration-upload',
						'OPTIONS registration-upload' => 'options',

                        'POST update-visibility-ranks'	=> 'update-visibility-ranks',
						'OPTIONS update-visibility-ranks' => 'options',
                        
					],
				],
                
            ]
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {

                $response = $event->sender;
                if($response->format == 'html') {
                    return $response;
                }

                $responseData = $response->data;

                if(is_string($responseData) && json_decode($responseData)) {
                    $responseData = json_decode($responseData, true);
                }


                if($response->statusCode >= 200 && $response->statusCode <= 299) {
                    $response->data = [
                        'success'   => true,
                        'status'    => $response->statusCode,
                        'data'      => $responseData,
                    ];
                } else {
                    $response->data = [
                        'success'   => false,
                        'status'    => $response->statusCode,
                        'data'      => $responseData,
                    ];

                }
                return $response;
            },
        ],
        'sse' => [
	        'class' => \odannyc\Yii2SSE\LibSSE::class
        ]

    ],
    'modules' => [
        'v1' => [
            'class' => 'app\modules\v1\Module',
        ],
        'rbac' => [
            'class' => 'yii2mod\rbac\Module',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
