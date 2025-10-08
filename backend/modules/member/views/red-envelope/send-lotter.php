<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
/* @var $this yii\web\View */
/* @var $model common\models\member\RedEnvelope */
/* @var $form yii\widgets\ActiveForm */

$this->title = '赠送';
$this->params['breadcrumbs'][] = ['label' => 'Red Envelopes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$level_array = \yii\helpers\ArrayHelper::map(\common\models\member\Level::find()->asArray()->all(), 'level', 'name');
$level_array[0] = "全部";
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
                    'id' => $model->formName(),
                    'enableAjaxValidation' => true,
                    'validationUrl' => \common\helpers\Url::to(['send-lotter']),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'level', ['options' => ['class' => ['chart']]])->dropDownList($level_array) ?>
                    <?= $form->field($model, 'number', ['options' => ['class' => ['chart']]])->textInput() ?>
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
