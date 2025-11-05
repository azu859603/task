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
                <div class="col-sm-12">
                    <div class="nav-tabs-custom">
                        <ul class="nav nav-tabs">
                            <?php
                            foreach (\common\models\common\Languages::find()->select(['code', 'name'])->orderBy(['sort' => SORT_ASC])->asArray()->all() as $language) {
                                if ($language['code'] == $lang) {
                                    echo '<li class="active"><a href="' . \common\helpers\Url::to(["edit", "lang" => $language['code'], "id" => $model->id]) . '" aria-expanded="true">' . $language['name'] . '</a></li>';
                                } else {
                                    echo '<li><a href="' . \common\helpers\Url::to(["edit", "lang" => $language['code'], "id" => $model->id]) . '" aria-expanded="false">' . $language['name'] . '</a></li>';
                                }
                            }
                            ?>
                        </ul>
                        <div class="tab-content">
                            <div id=class="tab-pane">
                                <div class="panel-body">
                                    <?= $form->field($model_translations, 'content', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
                                        'type' => 'files',
                                        'theme' => 'default',
                                        'config' => [
                                            'pick' => [
                                                'multiple' => false,
                                            ],
                                            'formData' => [
                                                'drive' => Yii::$app->debris->backendConfig('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                                            ],
                                        ]
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?= $form->field($model, 'pid', ['options' => ['class' => ['chart']]])->dropDownList(\yii\helpers\ArrayHelper::map(\common\models\common\ImgCategory::find()->asArray()->all(), 'id', 'title')) ?>
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput(['maxlength' => true]) ?>
                    <?= $form->field($model, 'jump_type', ['options' => ['class' => ['chart']]])->radioList([1 => '站外', 0 => '站内']) ?>
                    <?= $form->field($model, 'jump_url', ['options' => ['class' => ['chart']]])->textInput(['maxlength' => true, 'placeholder' => '此处若未空,则点击图片不进行跳转'])->hint("*若是站内链接请输入内容链接", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
<!--                        <span class="btn btn-white" onclick="history.go(-1)">返回</span>-->
                        <a class="btn btn-white" href="<?= \common\helpers\Url::to("index")?>">返回</a>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
