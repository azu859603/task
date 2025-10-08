<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\tea\ActivityCardList */
/* @var $form yii\widgets\ActiveForm */

$this->title = '添加虎卡';
$this->params['breadcrumbs'][] = ['label' => '虎卡列表', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                class="sr-only">关闭</span>
    </button>
    <h4 class="modal-title">添加虎卡</h4>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'id' => 'ajax_edit',
                    'enableAjaxValidation' => true,
                    'class' => 'form-horizontal',
                    'validationUrl' => Url::to(['ajax-edit']),
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
                    <?= $form->field($model, 'member_id', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '请选择会员账号'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "/backend/member/member/get-user",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ]) ?>

                    <?= $form->field($model, 'p1_number', ['options' => ['class' => ['chart']]])->textInput(['value' => '0']) ?>
                    <?= $form->field($model, 'p2_number', ['options' => ['class' => ['chart']]])->textInput(['value' => '0']) ?>
                    <?= $form->field($model, 'p3_number', ['options' => ['class' => ['chart']]])->textInput(['value' => '0']) ?>
                    <?= $form->field($model, 'p4_number', ['options' => ['class' => ['chart']]])->textInput(['value' => '0']) ?>
                    <?= $form->field($model, 'p5_number', ['options' => ['class' => ['chart']]])->textInput(['value' => '0']) ?>
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
