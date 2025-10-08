<?php


namespace backend\modules\tea\forms;


use yii\base\Model;
use yii\web\UploadedFile;

class ImportForm extends Model
{
    public $file;

    public function rules()
    {
        return [
            [['file'], 'file', 'extensions' => 'xls,xlsx', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => '上传文件',
        ];
    }
}