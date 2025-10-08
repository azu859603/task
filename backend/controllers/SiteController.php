<?php

namespace backend\controllers;

use common\helpers\GoogleAuthenticatorHelper;
use common\helpers\ResultHelper;
use common\models\backend\Member;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\behaviors\ActionLogBehavior;
use backend\forms\LoginForm;

/**
 * Class SiteController
 * @package backend\controllers
 * @author 原创脉冲
 */
class SiteController extends Controller
{
    /**
     * 默认布局文件
     *
     * @var string
     */
    public $layout = "default";

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'captcha', 'create', 'test','google'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'actionLog' => [
                'class' => ActionLogBehavior::class
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'maxLength' => 6, // 最大显示个数
                'minLength' => 6, // 最少显示个数
                'padding' => 5, // 间距
                'height' => 32, // 高度
                'width' => 100, // 宽度
                'offset' => 4, // 设置字符偏移量
                'backColor' => 0xffffff, // 背景颜色
                'foreColor' => 0x62a8ea, // 字体颜色
            ]
        ];
    }

    /**
     * 登录
     *
     * @return string|\yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            // 记录行为日志
            Yii::$app->services->actionLog->create('login', '自动登录', false);

            return $this->goHome();
        }

        $model = new LoginForm();
//        $model->loginCaptchaRequired();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // 记录行为日志
            Yii::$app->services->actionLog->create('login', '账号登录', false);
            // 只能登录一个端
            $last_session = Yii::$app->security->generateRandomString();
            Yii::$app->session->set("session_" . trim($model->username), $last_session);
            $manager = Member::findOne(Yii::$app->user->id);
            $manager->last_session = $last_session;
            $manager->save(false);

            return $this->goHome();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * 生成二维码和key
     */
    public function actionCreate()
    {

        $Google = new GoogleAuthenticatorHelper();
        // 生成keys
        $secret = $Google->createSecret();
        $name = Yii::$app->request->get('name') ?? Yii::$app->request->getHostInfo();//谷歌验证码里面的标识符
        $qrCodeUrl = $Google->getQRCodeGoogleUrl($name, $secret); //第一个参数是"标识",第二个参数为"安全密匙SecretKey" 生成二维码信息
        // 数据库存入keys 和对应的二维码
        echo "第一次生成keys：" . $secret . "，二维码扫描连接：" . $qrCodeUrl; //Google Charts接口 生成的二维码图片,方便手机端扫描绑定安全密匙SecretKey
    }

    public function actionTest()
    {
//        session_start();   // 删除所有 Session 变量
        $_SESSION = array();   //判断 cookie 中是否保存 Session ID
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        //彻底销毁 Session
        session_destroy();


        exit("完成");
    }

    public function actionGoogle(){
        $username = Yii::$app->request->post('username');
        $model = Member::findOne(['username'=>$username]);
        if(!empty($model) && $model['google_switch'] == 1){
            return ResultHelper::json(200, 'OK');
        }else{
            return ResultHelper::json(400, 'ERROR');
        }
    }
}
