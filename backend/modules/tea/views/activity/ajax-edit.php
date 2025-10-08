<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\Activity */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Activities', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                class="sr-only">关闭</span>
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
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>

                    <?= $form->field($model, 'open_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                        'options' => ['value' => !empty($model->open_time) ? date("Y-m-d H:i:s", $model->open_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ]) ?>

                    <?= $form->field($model, 'stop_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                        'options' => ['value' => !empty($model->stop_time) ? date("Y-m-d H:i:s", $model->stop_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ]) ?>

                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>

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
