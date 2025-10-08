<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/24
 * Time: 2:17
 */

namespace api\modules\v1\forms\common;

use common\models\common\ImgDetails;
use yii\base\Model;

class ImgForm extends Model
{
    public $pid;

    public function rules()
    {
        return [
            [['pid'], 'required'],
            ['pid', 'in', 'range' => ImgDetails::getImgPidArray()]
        ];
    }

    public function attributeLabels()
    {
        return [
            'pid' => '标识符',
        ];
    }
}