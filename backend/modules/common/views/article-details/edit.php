<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\common\ArticleDetails */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Article Details', 'url' => ['index']];
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
                        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
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
                                    <?= $form->field($model_translations, 'content', ['options' => ['class' => ['chart']]])->widget(\common\widgets\ueditor\UEditor::class, []) ?>
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
                    <?php

//                    $default_lang_model = \common\models\common\Languages::find()->select(['code'])->where(['is_default' => 1])->one();
                    $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
//                    $lang = Yii::$app->request->get('lang', $default_lang);
                    $lang = $default_lang;

                    $articleCategorys = \common\models\common\ArticleCategory::find()
                        ->with(['translation' => function ($query) use ($lang) {
                            $query->where(['lang' => $lang]);
                        }])
                        ->asArray()
                        ->all();
                    $articleCategory = [];
                    foreach ($articleCategorys as $k => $v) {
                        $id = $v['id'];
                        $articleCategory[$id] = !empty($v['translation']['title']) ? $v['translation']['title'] : "暂无";
                    }
                    ?>
                    <?= $form->field($model, 'pid', ['options' => ['class' => ['chart']]])->dropDownList($articleCategory) ?>
                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                    <?= $form->field($model, 'created_at', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                        'options' => ['value' => !empty($model->created_at) ? date("Y-m-d H:i:s", $model->created_at) : date("Y-m-d H:i:s"), 'readonly' => true],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ]) ?>
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
