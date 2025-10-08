<?php
// +----------------------------------------------------------------------------------------
// | 原创项目
// +----------------------------------------------------------------------------------------
// | 版权所有 原创脉冲工作室
// +----------------------------------------------------------------------------------------
// |  联系方式：
// |  QQ：123546
// |  skype：123546
// |  Telegram：@123546
// +----------------------------------------------------------------------------------------
// | 开发团队:原创脉冲
// +----------------------------------------------------------------------------------------

namespace api\modules\v1\forms\member;


use common\models\api\AccessToken;
use common\models\member\Member;
use yii\base\Model;
use Yii;

class UpdatePasswordForm extends Model
{
    public $old_password;
    public $new_password;
    public $new_password_repetition;
    public $_member;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['old_password', 'new_password', 'new_password_repetition'], 'required'],
            [['old_password', 'new_password', 'new_password_repetition'], 'string', 'min' => 6],
            [['old_password', 'new_password', 'new_password_repetition'], 'string', 'max' => 20],
            [['new_password_repetition'], 'compare', 'compareAttribute' => 'new_password', 'message' => '您输入的俩次密码不一致'],// 验证新密码和重复密码是否相等
            ['new_password', 'validateNewPassword'],
        ];
    }

    public function validateNewPassword($attribute)
    {
        if (!$this->hasErrors()) {
            $member = $this->getMember();
            if (!$member || !$member->validatePassword($this->old_password)) {
                $this->addError($attribute, '旧密码错误!');
            }
        }
    }

    /**
     * 获取信息
     * @return Member|null
     */
    public function getMember()
    {
        if ($this->_member === null) {
            $this->_member = Member::findOne(Yii::$app->user->identity['member_id']);
        }
        return $this->_member;
    }


    public function attributeLabels()
    {
        return [
            'old_password' => '旧密码',
            'new_password' => '新密码',
            'new_password_repetition' => '确定新密码',
        ];
    }
}