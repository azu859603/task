<?php

use common\models\common\Languages;
use yii\widgets\ActiveForm;
use common\enums\GenderEnum;
use common\enums\StatusEnum;
use yii\web\JsExpression;

$this->title = '编辑';
$this->params['breadcrumbs'][] = ['label' => '会员信息', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}{hint}{error}</div>",
                ],
            ]); ?>
            <div class="box-body">
                <?= $form->field($model, 'mobile')->textInput() ?>
                <?= $form->field($model, 'username')->textInput() ?>
                <?= $form->field($model, 'safety_password')->textInput(['placeholder' => '若不输入则不更改']); ?>
                <?= $form->field($model, 'head_portrait')->widget(\common\widgets\webuploader\Files::class, [
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

                <?= $form->field($model, 'nickname')->textInput() ?>
                <?= $form->field($model, 'promo_code')->textInput() ?>
                <?= $form->field($model->account, 'alipay_account')->textInput() ?>
                <?= $form->field($model->account, 'alipay_user_name')->textInput() ?>
                <?= $form->field($model->account, 'platform_account')->textInput() ?>
                <?= $form->field($model->account, 'gcash_name')->textInput() ?>
                <?= $form->field($model->account, 'gcash_phone')->textInput() ?>
                <?= $form->field($model->account, 'maya_name')->textInput() ?>
                <?= $form->field($model->account, 'maya_phone')->textInput() ?>
                <?= $form->field($model->account, 'facebook_account')->textInput() ?>
                <?= $form->field($model->account, 'instagram_account')->textInput() ?>
                <?= $form->field($model->account, 'tiktok_account')->textInput() ?>
                <?= $form->field($model->account, 'youtube_account')->textInput() ?>
                <?= $form->field($model, 'withdraw_switch')->radioList(['1' => '开启', '0' => '关闭']) ?>
                <?= $form->field($model, 'status')->radioList(StatusEnum::getMap()) ?>
            </div>
            <div class="box-footer text-center">
                <button class="btn btn-primary" type="submit">保存</button>
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    var b_id = document.getElementById('b_id')
    var type = <?= $model->type?>;
    if (type === 2) {
        b_id.setAttribute('style', '')
    } else {
        b_id.setAttribute('style', 'display:none;')
    }

    function checkOption(that) {
        if (that.value == 2) {
            b_id.setAttribute('style', '')
        } else {
            b_id.setAttribute('style', 'display:none;')
        }
    }
</script>