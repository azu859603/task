<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model common\models\dj\Orders */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Orders', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">关闭</span>
    </button>
    <h4 class="modal-title">订单</h4>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'id' => $model->formName(),
                    'enableAjaxValidation' => true,
                    'validationUrl' => \common\helpers\Url::to(['ajax-edit', 'id' => $model['id']]),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?php
                    $formatJs = <<< JS
var formatRepo = function (repo) {
    if (repo.loading) {
        return repo.text;
    }
    return repo.mobile;
};
var formatRepoSelection = function (repo) {
    return repo.mobile || repo.text;
}
JS;
                    $this->registerJs($formatJs, \yii\web\View::POS_HEAD);

                    $resultsJs = <<< JS
function (data, params) {
    params.page = params.page || 1;
    return {
        results: data.data,
        pagination: {
            more: (params.page * 20) < data.message
        }
    };
}
JS;
                    ?>
                    <?= $form->field($model, 'member_id',['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '请选择买家'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "/backend/member/member/get-member",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ])->label('买家'); ?>

                    <?= $form->field($model, 'seller_id',['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '请选择卖家'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "/backend/member/member/get-seller",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ])->label('卖家'); ?>

                    <?= $form->field($model, 'pid',['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '请选择剧集'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "/backend/dj/orders/get-short-plays",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ])->label('剧集'); ?>
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
