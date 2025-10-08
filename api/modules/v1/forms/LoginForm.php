<?php

namespace api\modules\v1\forms;

use Yii;
use common\enums\StatusEnum;
use common\models\member\Member;
use common\enums\AccessTokenGroupEnum;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class LoginForm
 * @package api\modules\v1\forms
 * @author 原创脉冲
 */
class LoginForm extends \common\models\forms\LoginForm
{
    public $group;
    public $mobile;
    public $drive;
    public $verifyCode;
    public $phoneIdentifier;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $required_array = ['mobile', 'password', 'group', 'drive'];
        if (Yii::$app->debris->config('verify_code_switch')) {
            $required_array[] = 'verifyCode';
            $required_array[] = 'phoneIdentifier';
        }
        return [
            [$required_array, 'required'],
            ['password', 'validatePassword'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
            ['drive', 'in', 'range' => array_keys(Member::$driveExplain)],
            ['verifyCode', 'validateVerifyCode'],
        ];
    }

    /**
     * 验证图形验证码
     * @param $attribute
     * @throws UnprocessableEntityHttpException
     */
    public function validateVerifyCode($attribute)
    {
        if (!$this->hasErrors()) {
            $key = Yii::$app->params['thisAppEnglishName'] . $this->phoneIdentifier;
            $redis = Yii::$app->redis;
            $redis->select(1);
            $verifyCode = $redis->get($key);
            if ($verifyCode != $this->verifyCode) {
                throw new UnprocessableEntityHttpException('验证码错误');
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'mobile' => '账号',
            'password' => '登录密码',
            'group' => '组别',
            'drive' => '机型',
            'verifyCode' => '验证码',
            'phoneIdentifier' => '手机唯一标识符',
        ];
    }

    /**
     * 用户登录
     * @return array|mixed|\yii\db\ActiveRecord|null
     * @throws UnprocessableEntityHttpException
     */
    public function getUser()
    {
        if ($this->_user == false) {
            $this->_user = Member::find()
                ->where(['mobile' => $this->mobile])
                ->andFilterWhere(['merchant_id' => Yii::$app->services->merchant->getId()])
                ->one();
        }

        if ($this->_user && $this->_user->status != StatusEnum::ENABLED) {
            throw new UnprocessableEntityHttpException('操作繁忙，请您联系在线客服！');
        }

        return $this->_user;
    }
}
