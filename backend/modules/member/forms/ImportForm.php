<?php
namespace backend\modules\member\forms;


use yii\base\Model;

class ImportForm extends Model
{
    public $file;
    public $content;
    public $title;

    public function rules()
    {
        return [
            [['file'], 'file', 'extensions' => 'txt', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
            [['content', 'title'], 'required']
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => '文件',
            'title' => '标题',
            'content' => '内容',
        ];
    }
}