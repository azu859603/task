<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model common\models\member\WithdrawBill */
/* @var $form yii\widgets\ActiveForm */

$this->title = '操作';
$this->params['breadcrumbs'][] = ['label' => '列表', 'url' => ['index']];
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
                        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'type', ['options' => ['class' => ['chart']]])->dropDownList([1=>"会员",2=>"管理"], ["onchange" => "checkOption(this)"]) ?>
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
                    <div id="m_id">
                    <?= $form->field($model, 'member_id',['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '选择成员'],
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
                    ])->label('添加成员'); ?>

                    <?php
                    $formatJs = <<< JS
var formatRepo = function (repo) {
    if (repo.loading) {
        return repo.text;
    }
    return repo.username;
};
var formatRepoSelection = function (repo) {
    return repo.username || repo.text;
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
                    </div>
                    <div id="b_id" style="display:none;">
                    <?= $form->field($model, 'b_id',['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'options' => ['placeholder' => '选择管理'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "/backend/member/member/get-backend-user",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ])->label('添加管理'); ?>
                </div>
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

<script type="text/javascript">
    function checkOption(that) {
        var b_id = document.getElementById('b_id')
        var m_id = document.getElementById('m_id')
        if (that.value == 2) {
            b_id.setAttribute('style', '')
            m_id.setAttribute('style', 'display:none;')
        } else {
            b_id.setAttribute('style', 'display:none;')
            m_id.setAttribute('style', '')
        }
    }
</script>
