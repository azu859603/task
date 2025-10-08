<?php

use yii\widgets\ActiveForm;

$this->title = '编辑';
$this->params['breadcrumbs'][] = ['label' => '公告管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}{hint}{error}</div>",
                ]
            ]); ?>
            <div class="box-body">
                <?= $form->field($model, 'title')->textInput() ?>
                <?= $form->field($model, 'content')->widget(\common\widgets\ueditor\UEditor::class) ?>
                <?= $form->field($model, 'created_at', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                    'options' => ['value' => !empty($model->created_at) ? date("Y-m-d H:i:s", $model->created_at) : date("Y-m-d H:i:s"), 'readonly' => true],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd hh:ii:ss',
                        'autoclose' => true,
                        'timePicker' => true,
                    ]
                ]) ?>
            </div>
            <!-- /.box-body -->
            <div class="box-footer">
                <div class="col-sm-12 text-center">
                    <button class="btn btn-primary" type="submit" onclick="SendForm()">保存</button>
                    <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>