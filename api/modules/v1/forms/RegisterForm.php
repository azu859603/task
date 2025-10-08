<?php

namespace api\modules\v1\forms;

use yii\base\Model;
use common\helpers\RegularHelper;
use common\models\member\Member;
use common\models\common\SmsLog;
use common\enums\AccessTokenGroupEnum;
use common\models\validators\SmsCodeValidator;
use Yii;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class RegisterForm
 * @package api\modules\v1\forms
 * @author 原创脉冲
 */
class RegisterForm extends Model
{
    public $mobile;
    public $password;
    public $password_repetition;
    public $code;
    public $group;
    public $promo_code;
    public $drive;
    public $pid = 0;
    public $phone_identifier;
    public $verifyCode;
    public $register_type;
    public $type;

    /**
     * @var Member
     */
    public $_parent;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $return_array = [
            [['password'], 'string', 'min' => 6],
            [['password'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 30],
            [['phone_identifier'], 'string', 'max' => 200],
            [
                ['mobile'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'mobile',
                'message' => '此{attribute}已存在'
            ],
//            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号码'],
            [['mobile'], 'trim'],
            ['mobile', 'validateMobile'],
            [['password_repetition'], 'compare', 'compareAttribute' => 'password'],// 验证新密码和重复密码是否相等
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
            ['promo_code', 'promoCodeVerify'],
            ['drive', 'in', 'range' => array_keys(Member::$driveExplain)],
            ['type', 'in', 'range' => array_keys(Member::$typeExplain)],
            ['verifyCode', 'validateVerifyCode'],

        ];

        // 定义必填字段
        $register_array = ['mobile', 'group', 'password', 'password_repetition', 'drive', 'register_type', 'type'];
        //如果短信验证码开启
        if (Yii::$app->debris->config('sms_switch')) {
            $register_array[] = 'code';
            $return_array[] = ['code', SmsCodeValidator::class, 'usage' => SmsLog::USAGE_REGISTER];
        }
        // 如果邀请码开启
        if (Yii::$app->debris->config('promo_code_switch')) {
            $register_array[] = 'promo_code';
        }
        //如果手机唯一标识符开关开启
        if (Yii::$app->debris->config('phone_identifier_switch')) {
//            $register_array[] = 'phone_identifier';
            $return_array[] = [
                ['phone_identifier'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'phone_identifier',
                'message' => '您当前的手机型号只能注册一个账号'
            ];
        }

        $return_array[] = [$register_array, 'required'];

        return $return_array;
    }

    /**
     * 验证图形验证码
     * @param $attribute
     * @throws UnprocessableEntityHttpException
     */
    public function validateVerifyCode($attribute)
    {
        if (!$this->hasErrors()) {
            $key = Yii::$app->params['thisAppEnglishName'] . $this->phone_identifier;
            $redis = Yii::$app->redis;
            $redis->select(1);
            $verifyCode = $redis->get($key);
            if ($verifyCode != $this->verifyCode) {
                throw new UnprocessableEntityHttpException('验证码错误');
            }
        }
    }

    /**
     * @param $attribute
     * @throws UnprocessableEntityHttpException
     */
    public function promoCodeVerify($attribute)
    {
        if ($this->promo_code) {
            $this->_parent = Yii::$app->services->member->findByPromoCode($this->promo_code);
            if (!$this->_parent) {
                throw new UnprocessableEntityHttpException('找不到推广员');
            } else {
                $this->pid = $this->_parent->id;
            }
        }
    }

    /**
     * 验证手机号码
     * @param $attribute
     */
    public function validateMobile($attribute)
    {
        if (!$this->hasErrors()) {
            $username = $this->mobile;
            if ($this->register_type == 1) {
                if (!preg_match(RegularHelper::mobile(), $username)) { // 国内
                    $this->addError($attribute, "请输入正确的手机号码");
                }
                $error_phone = explode('/', Yii::$app->debris->config('error_phone'));
                if (in_array(mb_substr($this->mobile, 0, 3), $error_phone)) {
                    $this->addError($attribute, "请输入真实的手机号码");
                }
            }
            if ($this->register_type == 2) {
                if (!preg_match(RegularHelper::email(), $username)) {
                    $this->addError($attribute, "请输入正确的邮箱");
                }
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'mobile' => '账号',
            'password' => '密码',
            'password_repetition' => '重复密码',
            'group' => '类型',
            'code' => '验证码',
            'drive' => '机型',
            'promo_code' => '邀请码',
            'phone_identifier' => '手机型号',
            'verifyCode' => '验证码',
            'register_type' => '账号类型',
        ];
    }

    /**
     * @return Member
     */
    public function getParent()
    {
        return $this->_parent;
    }
}