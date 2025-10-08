<?php

namespace common\models\common;

use common\models\backend\Member;
use Yii;

/**
 * This is the model class for table "i_article_category".
 *
 * @property int $id
 * @property string $title 文章分类名称
 * @property int $status 状态(1正常,0禁用)
 * @property int $sort 排序(越大越靠前)
 * @property int $created_at 添加时间
 * @property int $updated_at 修改时间
 * @property int $created_by 发布人
 * @property int $updated_by 修改人
 * @property int $type 是否进入详情
 * @property string $banner icon
 */
class ArticleCategory extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
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
    public static function tableName()
    {
        return 'base_article_category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sort'], 'required'],
            [['status', 'sort', 'created_at', 'updated_at', 'created_by', 'updated_by', 'type'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['banner'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '分类名称',
            'status' => '状态',
            'sort' => '排序',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'created_by' => '发布人',
            'updated_by' => '修改人',
            'banner' => '图标',
            'type' => '是否进入详情',
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

    public function getTranslation()
    {
        return $this->hasOne(ArticleCategoryTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ArticleCategoryTranslations::class, ['pid' => 'id']);
    }

}
