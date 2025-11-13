<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use yii\web\JsExpression;


$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'class' => 'form-horizontal',
    'validationUrl' => Url::to(['ajax-edit', 'id' => $model['id']]),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>

<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <h4 class="modal-title">基本信息</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'mobile')->textInput([
            'readonly' => !empty($model->mobile)
        ])->hint('创建后不可修改') ?>
        <?= $form->field($model, 'password_hash')->passwordInput() ?>

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
        <?php
        if (Yii::$app->params['thisAppEnglishName'] == "task_cn") {
            if (empty($model->mobile)) {
                echo $form->field($model, 'pid', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                    'data' => \yii\helpers\ArrayHelper::map(\common\models\member\Member::find()->asArray()->all(), 'id', 'mobile'),
                    'options' => ['placeholder' => '请选择上级账号'],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'ajax' => [
                            'url' => "get-user",
                            'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                            'processResults' => new JsExpression($resultsJs),
                            'cache' => true
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('formatRepo'),
                        'templateSelection' => new JsExpression('formatRepoSelection'),
                    ],
                ])->label('上级');
            }
        } else {
            echo $form->field($model, 'pid', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                'data' => \yii\helpers\ArrayHelper::map(\common\models\member\Member::find()->asArray()->all(), 'id', 'mobile'),
                'options' => ['placeholder' => '请选择上级账号'],
                'pluginOptions' => [
                    'allowClear' => true,
                    'ajax' => [
                        'url' => "get-user",
                        'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                        'processResults' => new JsExpression($resultsJs),
                        'cache' => true
                    ],
                    'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    'templateResult' => new JsExpression('formatRepo'),
                    'templateSelection' => new JsExpression('formatRepoSelection'),
                ],
            ])->label('上级');
        }

        ?>
        <?= $form->field($model, 'type', ['options' => ['class' => ['chart']]])->dropDownList(\common\models\member\Member::$typeExplain, ["onchange" => "checkOption(this)"]) ?>




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
        <div id="b_id">
            <?= $form->field($model, 'b_id', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                'options' => ['placeholder' => '请选择后台代理账号', 'value' => \yii\helpers\ArrayHelper::map(\common\models\backend\Member::find()->asArray()->all(), 'id', 'username')[$model->b_id],],

                'pluginOptions' => [
                    'allowClear' => true,
                    'ajax' => [
                        'url' => "get-backend-user",
                        'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                        'processResults' => new JsExpression($resultsJs),
                        'cache' => true
                    ],
                    'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    'templateResult' => new JsExpression('formatRepo'),
                    'templateSelection' => new JsExpression('formatRepoSelection'),
                ],
            ])->label('后台代理'); ?>
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>
</div>

<?php ActiveForm::end(); ?>

<script type="text/javascript">
    var b_id = document.getElementById('b_id')
    var type = <?= $model->type?>;
    if (type === 2) {
        b_id.setAttribute('style', '')
    } else {
        b_id.setAttribute('style', 'display:none;')
    }

    function checkOption(that) {
        if (that.value == 2) {
            b_id.setAttribute('style', '')
        } else {
            b_id.setAttribute('style', 'display:none;')
        }
    }
</script>
