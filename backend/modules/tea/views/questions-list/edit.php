<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\QuestionsList */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Questions Lists', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"></h3>
            </div>
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'type', ['options' => ['class' => ['chart']]])->radioList(['1' => '单选', '2' => '多选']) ?>
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'content')->widget(\unclead\multipleinput\MultipleInput::class, [
                        'max' => 10,
                        'columns' => [
                            [
                                'name' => 'key',
                                'title' => '参数名',
                                'enableError' => false,
                                'options' => [
                                    'class' => 'input-priority'
                                ]
                            ],
                            [
                                'name' => 'value',
                                'title' => '参数值',
                                'enableError' => false,
                                'options' => [
                                    'class' => 'input-priority'
                                ]
                            ],
                        ]
                    ]);
                    ?>
                    <?= $form->field($model, 'answer', ['options' => ['class' => ['chart']]])->textInput()->hint("*若有多个答案，请用“/”隔开", ['style' => 'color:red']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
