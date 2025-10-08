<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\dj\SellerLevel */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Seller Levels', 'url' => ['index']];
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
                                    <?= $form->field($model_translations, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                                    <?= $form->field($model_translations, 'detail', ['options' => ['class' => ['chart']]])->textInput() ?>
                                    <?= $form->field($model_translations, 'banner', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
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
                            </div>
                        </div>
                    </div>
                    <?= $form->field($model, 'level')->widget(\kartik\select2\Select2::class, [
                        'data' => \common\enums\MemberLevelEnum::getMap(),
                        'options' => ['placeholder' => '请选择'],
                        'pluginOptions' => [
                            'allowClear' => true
                        ],
                    ])->hint("*数字越大等级越高", ['style' => 'color:red']); ?>
                    <?= $form->field($model, 'buy_money', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'push_flow', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'profit', ['options' => ['class' => ['chart']]])->textInput()->hint("*上架短剧的利润百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'return_income_time', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：天", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'profit_rebate', ['options' => ['class' => ['chart']]])->textInput()->hint("*未上架短剧的利润百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'can_available_switch')->radioList(['1' => '能', '0' => '否']) ?>
                    <?= $form->field($model, 'handling_fees_percentage', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'status')->radioList(\common\enums\StatusEnum::getMap()) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <a class="btn btn-white" href="<?= \common\helpers\Url::to("index") ?>">返回</a>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
