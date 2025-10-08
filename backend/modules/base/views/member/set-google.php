<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\common\ImgDetailsTranslations */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Img Details Translations', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
    .delimg{
        display: none;
    }
</style>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"></h3>
            </div>
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
<!--                https://api.qrserver.com/v1/create-qr-code/?data=otpauth%3A%2F%2Ftotp%2F%E7%9F%AD%E5%89%A7%28账号%29%3Fsecret%3D密钥&size=200x200&ecc=M-->
                <div class="col-sm-12">
                    <?= $form->field($model, 'google_switch', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                    <?= $form->field($model, 'google_secret', ['options' => ['class' => ['chart']]])->textInput(['readonly'=>'readonly']) ?>
                    <?= $form->field($model, 'google_url', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
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

                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
