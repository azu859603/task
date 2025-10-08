<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_gdt_add1".
 *
 * @property int $id
 * @property string $muid 设备ID
 * @property int $click_time 点击时间
 * @property string $app_type 机型
 * @property string $ip IP
 * @property int $is_register 是否注册APP(0未注册，1注册)
 */
class GdtAdd1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_gdt_add1';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['muid', 'click_time', 'app_type', 'ip'], 'required'],
            [['click_time', 'is_register'], 'integer'],
            [['muid'], 'string', 'max' => 100],
            [['app_type'], 'string', 'max' => 20],
            [['ip'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'muid' => '设备ID',
            'click_time' => '点击时间',
            'app_type' => '机型',
            'ip' => 'IP',
            'is_register' => '是否注册APP(0未注册，1注册)',
        ];
    }
}
