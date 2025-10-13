<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\dj\LaberList */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Laber Lists', 'url' => ['index']];
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
                                    <?= $form->field($model_translations, 'content', ['options' => ['class' => ['chart']]])->textarea() ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
                    $lang = $default_lang;
                    $laberLists = \common\models\task\LaberList::find()->with(['translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }])
                        ->asArray()
                        ->all();
                    foreach ($laberLists as $k => $v) {
                        $id = $v['id'];
                        $laberList[$id] = !empty($v['translation']['title']) ? $v['translation']['title'] : "暂无";
                    }
                    ?>
                    <?= $form->field($model, 'pid', ['options' => ['class' => ['chart']]])->dropDownList($laberList) ?>
                    <?= $form->field($model, 'banner', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
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
                    <?= $form->field($model, 'all_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'vip_level', ['options' => ['class' => ['chart']]])->dropDownList(\yii\helpers\ArrayHelper::map(\common\models\member\Level::find()->asArray()->all(), 'level', 'name'))->hint("*会员等级必须大于等于此等级才能接任务", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'money', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'limit_number', ['options' => ['class' => ['chart']]])->textInput()->hint("*单个会员最多重复领取该任务的次数", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'code_switch', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                    <?= $form->field($model, 'images_list', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
                        'type' => 'images',
                        'theme' => 'default',
                        'config' => [
                            'pick' => [
                                'multiple' => true,
                            ],
                            'formData' => [
                                'drive' => Yii::$app->debris->config('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                            ],
                        ]
                    ]); ?>
                    <?= $form->field($model, 'file_list', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
                        'type' => 'images',
                        'theme' => 'default',
                        'config' => [
                            'pick' => [
                                'multiple' => true,
                            ],
                            'formData' => [
                                'drive' => Yii::$app->debris->config('backend_upload_drive'),// 默认本地 支持 qiniu/oss 上传
                            ],
                        ]
                    ]); ?>
                    <?= $form->field($model, 'keywords', ['options' => ['class' => ['chart']]])->textInput()->hint("*多个用“|”隔开", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'experience', ['options' => ['class' => ['chart']]])->textInput() ?>


                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
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
