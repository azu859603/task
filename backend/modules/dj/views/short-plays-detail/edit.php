<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\dj\ShortPlaysDetail */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Short Plays Details', 'url' => ['index']];
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
                            foreach (\common\models\common\Languages::find()->where(['code'=>'cn'])->select(['code', 'name'])->orderBy(['sort' => SORT_ASC])->asArray()->all() as $language) {
                                if ($language['code'] == $lang) {
                                    echo '<li class="active"><a href="' . \common\helpers\Url::to(["edit", "lang" => $language['code'], "id" => $model->id, "pid" => $pid]) . '" aria-expanded="true">' . $language['name'] . '</a></li>';
                                } else {
                                    echo '<li><a href="' . \common\helpers\Url::to(["edit", "lang" => $language['code'], "id" => $model->id, "pid" => $pid]) . '" aria-expanded="false">' . $language['name'] . '</a></li>';
                                }
                            }
                            ?>
                        </ul>
                        <div class="tab-content">
                            <div id=class="tab-pane">
                                <div class="panel-body">
                                    <?= $form->field($model_translations, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                                    <?= $form->field($model_translations, 'content', ['options' => ['class' => ['chart']]])->textInput() ?>
                                    <?= $form->field($model_translations, 'banner', ['options' => ['class' => ['chart']]])->textInput() ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?= $form->field($model, 'number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'like_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'status')->radioList(\common\enums\StatusEnum::getMap()) ?>
                    <?= $form->field($model, 'type')->radioList(['0' => '免费', 1 => '收费']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <a class="btn btn-white"
                           href="<?= \common\helpers\Url::to(["index", "pid" => $pid]) ?>">返回</a>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
