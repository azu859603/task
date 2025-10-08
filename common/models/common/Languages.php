<?php

namespace common\models\common;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_languages".
 *
 * @property int $id
 * @property string $name 语言名称
 * @property string $code 代码
 * @property int $status 状态
 * @property int $sort
 * @property int $is_default
 */
class Languages extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_languages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'code'], 'required'],
            [['status', 'sort','is_default'], 'integer'],
            [['name', 'code'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '语言名称',
            'code' => '代码',
            'status' => '状态',
            'sort' => '排序',
            'is_default' => '默认语言',
        ];
    }
}
