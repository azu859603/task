<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\helpers\Html;
use yii\web\JsExpression;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit','id'=>$id]),
]);
?>

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <h4 class="modal-title">基本信息</h4>
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
        <?php
            if(empty($id)){
                echo $form->field($model, 'toManagerId', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                    'options' => ['placeholder' => '查询用户', 'multiple' => true,],
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
                ]);
            }
        ?>

        <?= $form->field($model, 'title')->textInput() ?>
        <?= $form->field($model, 'content')->widget(\common\widgets\ueditor\UEditor::class) ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>

<?php Html::modelBaseCss(); ?>
<?php ActiveForm::end(); ?>