<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This is the model class for table "t_questions_list".
 *
 * @property int $id
 * @property int $type
 * @property string $title 题目
 * @property array $content 选项
 * @property string $answer 答案
 */
class QuestionsList extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_questions_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content', 'answer'], 'required'],
            [['content'], 'safe'],
            [['type'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['answer'], 'string', 'max' => 50],
            [['title',], 'unique', 'message' => '此题目已存在！'],
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->content) {
            $this->content = Json::encode($this->content);
        }

        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        if ($this->content) {
            $this->content = Json::decode($this->content);
        }

        parent::afterFind();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '题目',
            'content' => '选项',
            'answer' => '答案',
            'type' => '类型',
        ];
    }
}
