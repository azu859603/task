<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use yii\web\JsExpression;


$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'class' => 'form-horizontal',
    'validationUrl' => Url::to(['ajax-edit-buyer']),
    'fieldConfig' => [
        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ]
]);
?>



<style>
    .loading {
        position: relative;
        width: 20px;
        height: 20px;
        border: 2px solid #000;
        border-top-color: rgba(0, 0, 0, 0.2);
        border-right-color: rgba(0, 0, 0, 0.2);
        border-bottom-color: rgba(0, 0, 0, 0.2);
        border-radius: 100%;

        animation: circle infinite 0.75s linear;
    }

    @keyframes circle {
        0% {
            transform: rotate(0);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <h4 class="modal-title">基本信息</h4>
        <div id="loading" style="display: none">
            <div style="display: flex;align-items: center;justify-content: center;gap: 10px;">
                <div>保存中</div>
                <div class="loading"></div>
            </div>
        </div>


    </div>
    <div class="modal-body">


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
        <?= $form->field($model, 'pid', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
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
        ])->label('上级'); ?>

        <?= $form->field($model, 'password')->textInput()->hint("*账号随机生成", ['style' => 'color:red']) ?>
        <?= $form->field($model, 'number')->textInput() ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button id="button" class="btn btn-primary" type="submit">保存</button>
    </div>
</div>

<?php ActiveForm::end(); ?>
<script>
    $("#button").click(function () {
        $(this).prop("disabled", true); // 禁用按钮
        document.getElementById("loading").style.display = "block";
        $(this).submit();
    });
</script>