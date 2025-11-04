<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/24
 * Time: 19:48
 */

namespace api\modules\v1\forms\member;


use common\helpers\RegularHelper;
use common\models\common\SmsLog;
use common\models\member\Member;
use common\models\validators\SmsCodeValidator;
use yii\base\Model;
use yii\web\UnprocessableEntityHttpException;
use Yii;
class UpdateMemberForm extends Model
{
    public $scenario_type;
    public $nickname;
    public $realname;
    public $identification_number;
    public $head_portrait;
    public $mobile;
    public $code;
    public $email;
    public $automatic_delivery_switch;
    public $push_flow_switch;
    public $register_type;
    public $username;

    public $scenario_type_array = [
        'nickname', 'head_portrait', 'mobile', 'realname', 'email', 'automatic_delivery_switch','push_flow_switch','register_type','username'
    ];

    public function rules()
    {
        return [
            [['scenario_type'], 'required'],
            [['mobile'], 'trim'],
//            [['code', 'mobile'], 'required', 'on' => 'mobile'],
            [['mobile','register_type'], 'required', 'on' => 'mobile'],
//            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号码'],
            ['mobile', 'validateMobile'],


            [['nickname'], 'required', 'on' => 'nickname'],
            [['nickname'], 'string', 'max' => 50, 'on' => 'nickname'],

            [['email'], 'required', 'on' => 'email'],
            [['username'], 'required', 'on' => 'username'],
            [
                ['username'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'username',
                'message' => '此{attribute}已存在。'
            ],


            [['email'], 'string', 'max' => 60, 'on' => 'email'],
            ['email', 'match', 'pattern' => RegularHelper::email(), 'message' => '请输入正确的邮箱'],

            [['realname', 'identification_number'], 'required', 'on' => 'realname'],
            [['realname'], 'string', 'max' => 50, 'on' => 'realname'],

            [['head_portrait'], 'required', 'on' => 'head_portrait'],

            [['automatic_delivery_switch'], 'integer', 'on' => 'automatic_delivery_switch'],
            [['push_flow_switch'], 'integer', 'on' => 'push_flow_switch'],

            ['code', SmsCodeValidator::class, 'usage' => SmsLog::USAGE_UP_MOBILE],
            [
                ['mobile'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'mobile',
                'message' => '此{attribute}已存在。'
            ],
            [
                ['email'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'email',
                'message' => '此{attribute}已存在。'
            ],
//            [
//                ['realname'],
//                'unique',
//                'targetClass' => Member::class,
//                'targetAttribute' => 'realname',
//                'message' => '此{attribute}已存在。'
//            ],
            [
                ['identification_number'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'identification_number',
                'message' => '此{attribute}已存在。'
            ],

            ['identification_number', 'match', 'pattern' => RegularHelper::identityCard(), 'message' => '请输入正确的身份证号码'],
            ['identification_number', 'identificationNumberVerify'],
        ];
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

    /**
     * @param $attribute
     */
    public function identificationNumberVerify($attribute)
    {
        if (!$this->hasErrors() && $this->identification_number) {
            $now_year = date("Y");
            $member_yaer = mb_substr($this->identification_number, 6, 4);
            if ($now_year - $member_yaer < 18) {
                $this->addError($attribute, "年龄18周岁以下不能进行实名认证");
                return;
            }
        }
    }


    public function attributeLabels()
    {
        return [
            'scenario_type' => '类型',
            'nickname' => '昵称',
            'head_portrait' => '头像',
            'code' => '验证码',
            'mobile' => '账号',
            'realname' => '真实姓名',
            'identification_number' => '身份证号码',
            'email' => "邮箱",
            'automatic_delivery_switch' => "发货开关",
            'push_flow_switch' => "推流开关",
            'username' => "手机号码",
        ];
    }
}