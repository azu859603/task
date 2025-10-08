<?php

namespace api\modules\v1\forms;

use common\enums\AppEnum;
use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\models\common\SmsLog;
use common\models\member\Member;
use common\models\validators\SmsCodeValidator;
use common\enums\AccessTokenGroupEnum;
use Yii;

/**
 * Class UpPwdForm
 * @package api\modules\v1\forms
 * @author 原创脉冲
 */
class UpSafetyPwdForm extends \common\models\forms\LoginForm
{
    public $mobile;
    public $safety_password;
    public $safety_password_repetition;
    public $code;
    public $group;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile', 'group', 'code', 'safety_password', 'safety_password_repetition'], 'required'],
            [['safety_password'], 'string', 'min' => 6],
            ['code', SmsCodeValidator::class, 'usage' => SmsLog::USAGE_UP_SAFETY_PWD],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号'],
            [['safety_password_repetition'], 'compare', 'compareAttribute' => 'safety_password'],// 验证新密码和重复密码是否相等
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
            ['safety_password', 'validateMobile'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'mobile' => '手机号码',
            'safety_password' => '支付密码',
            'safety_password_repetition' => '重复支付密码',
            'group' => '类型',
            'code' => '验证码',
        ];
    }

    /**
     * @param $attribute
     */
    public function validateMobile($attribute)
    {
        if (!$this->getUser()) {
            $this->addError($attribute, '找不到用户');
        }
    }

    /**
     * @return Member|mixed|null
     */
    public function getUser()
    {
        if ($this->_user == false) {
            $this->_user = Member::findOne(['mobile' => $this->mobile, 'status' => StatusEnum::ENABLED]);
        }

        return $this->_user;
    }
}