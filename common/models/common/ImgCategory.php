<?php

namespace common\models\common;

use common\enums\StatusEnum;
use common\models\backend\Member;
use Yii;


/**
 * This is the model class for table "task_img_category".
 *
 * @property int $id
 * @property string $title 图片分类名称
 * @property int $status 状态(1正常,0禁用)
 * @property int $sort 排序(越大越靠前)
 * @property int $created_at 添加时间
 * @property int $updated_at 修改时间
 * @property int $created_by 发布人
 * @property int $updated_by 修改人
 */
class ImgCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_img_category';
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\BlameableBehavior::className(),
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['status', 'sort', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],
            [['title'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '分类名',
            'status' => '状态',
            'sort' => '排序(越大越靠前)',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'created_by' => '发布人',
            'updated_by' => '修改人',
        ];
    }


    /**
     * 关联管理员
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getManager()
    {
        return $this->hasOne(Member::class, ['id' => 'created_by']);
    }

    public function getImgDetails()
    {
        return $this->hasMany(ImgDetails::class, ['pid' => 'id'])
            ->select(['pid','title' ,'content','jump_url','jump_type'])
            ->where(['status' => StatusEnum::ENABLED])
            ->orderBy(['sort' => SORT_DESC]);
    }
}
