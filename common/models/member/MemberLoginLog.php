<?php

namespace common\models\member;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "rf_member_login_log".
 *
 * @property int $id
 * @property int $member_id 会员ID
 * @property string $login_ip 登录IP
 * @property int $login_drive 登录驱动
 * @property int $created_at 登录时间
 */
class MemberLoginLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_member_login_log';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 'login_ip', 'login_drive', 'created_at'], 'required'],
            [['member_id', 'login_drive', 'created_at'], 'integer'],
            [['login_ip'], 'string', 'max' => 16],
        ];
    }

    public static $device = [
        1 => 'android',
        2 => 'ios',
        3 => 'web',
        4 => '无',
    ];

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员ID',
            'login_ip' => '登录IP',
            'login_drive' => '登录设备',
            'created_at' => '登录时间',
        ];
    }

    public static function createNewModel($member_id, $login_drive)
    {
        $model = new self();
        $model->member_id = $member_id;
        $model->login_drive = $login_drive;
        $model->login_ip = Yii::$app->request->getUserIP();
        if ($model->save(false)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }


}
