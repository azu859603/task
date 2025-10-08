<?php

namespace common\models\dj;

use common\models\common\Languages;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_laber_list".
 *
 * @property int $id
 * @property int $status 状态
 * @property int $sort
 */
class LaberList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_laber_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'sort'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => '状态',
            'sort' => '排序',
        ];
    }

    public function getTranslation()
    {
        return $this->hasOne(LaberListTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(LaberListTranslations::class, ['pid' => 'id']);
    }


}
