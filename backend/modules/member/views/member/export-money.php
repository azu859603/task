<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */

$this->title = '导出';
$this->params['breadcrumbs'][] = ['label' => '会员列表', 'url' => ['index']];
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
                    'class' => 'form-horizontal',
                    'validationUrl' => Url::to(['export-money']),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                    ]
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'start_money', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'stop_money', ['options' => ['class' => ['chart']]])->textInput() ?>
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
