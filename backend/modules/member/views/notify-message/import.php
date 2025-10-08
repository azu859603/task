<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\helpers\Html;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['import']),
]);
?>

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <h4 class="modal-title">基本信息</h4>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'file', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
            'type' => 'files',
            'theme' => 'default',
            'config' => [
                'pick' => [
                    'multiple' => false,
                ],
                'accept' => [
                    'extensions' => ["txt"],// 可上传图片后缀不填写即为不限
                ],
                'formData' => [
                    'drive' => Yii::$app->debris->config('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                ],
            ]
        ])->hint("只能上传TXT类型的文件", ['style' => 'color:red']); ?>
        <?= $form->field($model, 'title')->textInput() ?>
        <?= $form->field($model, 'content')->widget(\common\widgets\ueditor\UEditor::class) ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
        <button class="btn btn-primary" type="submit">保存</button>
    </div>

<?php Html::modelBaseCss(); ?>
<?php ActiveForm::end(); ?>