<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_register_ip".
 *
 * @property int $id
 * @property int $type 机型
 * @property string $ip IP
 * @property string $muid 设备ID
 * @property string $callback
 */
class RegisterIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_register_ip';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'ip'], 'required'],
            [['type'], 'integer'],
            [['muid','ip'], 'string', 'max' => 100],
            [['callback'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => '类型',
            'ip' => 'IP',
            'muid' => '设备ID',
            'callback' => 'callback',
        ];
    }
}