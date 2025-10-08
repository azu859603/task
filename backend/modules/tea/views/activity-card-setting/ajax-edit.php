<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\ActivityCardSetting */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Activity Card Settings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                class="sr-only">关闭</span>
    </button>
    <h4 class="modal-title">基本信息</h4>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'proportion', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'banner1', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
                        'type' => 'images',
                        'theme' => 'default',
                        'config' => [
                            'pick' => [
                                'multiple' => false,
                            ],
                            'formData' => [
                                'drive' => Yii::$app->debris->config('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                            ],
                        ]
                    ]); ?>
                    <?= $form->field($model, 'banner2', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
                        'type' => 'images',
                        'theme' => 'default',
                        'config' => [
                            'pick' => [
                                'multiple' => false,
                            ],
                            'formData' => [
                                'drive' => Yii::$app->debris->config('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                            ],
                        ]
                    ]); ?>
                    <?= $form->field($model, 'type', ['options' => ['class' => ['chart']]])->radioList(\common\models\tea\ActivityCardSetting::$typeExplain) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
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
