<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\common\ImgDetails */
/* @var $form yii\widgets\ActiveForm */

$this->title = '添加快递单号';
$this->params['breadcrumbs'][] = ['label' => '添加快递单号', 'url' => ['index']];
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
                    'id' => $model->formName(),
                    'enableAjaxValidation' => true,
                    'validationUrl' => \common\helpers\Url::to(['kuaidi', 'id' => $model['id']]),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'send_remark', ['options' => ['class' => ['chart']]])->textInput(['maxlength' => true]) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">

                        <button class="btn btn-primary" type="submit">确定</button>
                        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
