<?php

namespace addons\RfDevTool\common\models;

use yii\base\Model;

/**
 * Class MigrateForm
 * @package addons\RfDevTool\common\models
 * @author 原创脉冲
 */
class MigrateForm extends Model
{
    public $addon;
    public $tables = [];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tables', 'addon'], 'required'],
            [['tables'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'tables' => '表名',
            'addon' => '系统/插件',
        ];
    }
}