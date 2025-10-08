<?php
namespace frontend\forms;

use yii\base\Model;
use yii\db\ActiveQuery;
use common\enums\StatusEnum;
use common\models\member\Member;

/**
 * Class SignupForm
 * @package frontend\models
 */
class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;

    public $verifyCode;
    public $code;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['verifyCode', 'captcha'],
            [['username', 'email'], 'trim'],
            [['email', 'username', 'password'], 'required'],
            [
                'username',
                'unique',
                'targetClass' => '\common\models\member\Member',
                'filter' => function (ActiveQuery $query) {
                    return $query->andWhere(['>=', 'status', StatusEnum::DISABLED]);
                },
                'message' => '这个用户名已经被占用.'
            ],
            ['username', 'string', 'min' => 2, 'max' => 20],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            [
                'email',
                'unique',
                'targetClass' => '\common\models\member\Member',
                'filter' => function (ActiveQuery $query) {
                    return $query->andWhere(['>=', 'status', StatusEnum::DISABLED]);
                },
                'message' => '这个邮箱地址已经被占用了.'
            ],
            ['password', 'string', 'min' => 6, 'max' => 20],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username' => '手机号',
            'password' => '登录密码',
            'email' => '电子邮箱',
            'verifyCode' => '验证码',
            'code' => '短信验证码',
        ];
    }

    /**
     * 注册
     *
     * @return Member|null
     * @throws \yii\base\Exception
     */
    public function signup()
    {
        $user = new Member();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();

        return $user->save() ? $user : null;
    }
}
