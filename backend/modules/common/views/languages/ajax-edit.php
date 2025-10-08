<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\common\Languages */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Languages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$files = \yii\helpers\FileHelper::findFiles(Yii::getAlias('@root/web/flags'));
$filenames = [];
foreach ($files as $k => $path) {
    $filename = pathinfo($path)['filename'];
    $filenames[$filename] = $filename;
}
$url = Yii::$app->request->getHostInfo() . '/flags/';
$format = <<< SCRIPT
function format(state) {
    console.log(state)
    if (!state.id) return state.text; // optgroup
    src = '$url' +  state.id.toLowerCase() + '.png'
    return '<img class="flag" src="' + src + '"/> '+state.text;
}
SCRIPT;
$escape = new \yii\web\JsExpression("function(m) { return m; }");
$this->registerJs($format, \yii\web\View::POS_HEAD);
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">关闭</span>
    </button>
    <h4 class="modal-title">基本信息</h4>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'name', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?=
                    $form->field($model, 'code')->widget(\kartik\select2\Select2::class, [
                        'name' => 'state_12',
                        'data' => $filenames,
                        'options' => ['placeholder' => '请选择'],
                        'pluginOptions' => [
                            'allowClear' => true,
//                            'tags' => true,
                            'escapeMarkup' => $escape,
                            'templateResult' => new \yii\web\JsExpression('format'),
                            'templateSelection' => new \yii\web\JsExpression('format'),
                        ],
                    ])?>
                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                    <?= $form->field($model, 'is_default', ['options' => ['class' => ['chart']]])->radioList([1 => '是', 0 => '否']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
