<?php

namespace api\modules\v1\forms;

use yii\base\Model;
use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use common\models\member\Member;
use common\models\common\SmsLog;
use common\enums\AccessTokenGroupEnum;
use yii\web\UnprocessableEntityHttpException;
use Yii;

/**
 * Class MobileLogin
 * @package api\modules\v1\models
 * @author 原创脉冲
 */
class MobileLogin extends Model
{
    /**
     * @var
     */
    public $mobile;

    /**
     * @var
     */
    public $code;

    /**
     * @var
     */
    public $group;

    /**
     * @var
     */
    protected $_user;

    public $drive;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['mobile', 'code', 'group', 'drive'], 'required'],
            ['code', '\common\models\validators\SmsCodeValidator', 'usage' => SmsLog::USAGE_LOGIN],
            ['code', 'filter', 'filter' => 'trim'],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号'],
            ['mobile', 'validateMobile'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
            ['drive', 'in', 'range' => array_keys(Member::$driveExplain)],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'mobile' => '手机号码',
            'code' => '验证码',
            'group' => '组别',
            'drive' => '机型',
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