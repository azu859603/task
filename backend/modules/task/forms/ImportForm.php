<?php
namespace backend\modules\task\forms;


use yii\base\Model;

class ImportForm extends Model
{
    public $file;

    public function rules()
    {
        return [
            [['file'], 'file', 'extensions' => 'txt', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => '文件',
        ];
    }
}