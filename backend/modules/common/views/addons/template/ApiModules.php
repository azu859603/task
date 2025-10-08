<?php

echo "<?php\n";
?>

namespace addons\<?= $model->name;?>\<?= $appID ?>\modules\<?= $versions ?>;

/**
 * Class Module
 * @package addons\<?= $model->name;?>\<?= $appID ?>\modules\<?= $versions ?>
 * @author 原创脉冲
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'addons\<?= $model->name;?>\<?= $appID ?>\modules\<?= $versions ?>\controllers';

    public function init()
    {
        parent::init();
    }
}