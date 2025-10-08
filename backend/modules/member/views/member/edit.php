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

                <?php
//                $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
                $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
                $lang = Yii::$app->request->get('lang', $default_lang);
                $memberLevels = \common\models\dj\SellerLevel::find()
                    ->with([
                        'translation' => function ($query) use ($lang) {
                            $query->where(['lang' => $lang]);
                        }
                    ])
                    ->asArray()
                    ->all();
//                var_dump($memberLevels);
                foreach ($memberLevels as $k => $v) {
                    $id = $v['level'];
                    $memberLevel[$id] = !empty($v['translation']['title']) ? $v['translation']['title'] : "暂无";
                }
                ?>
                <?= $form->field($model, 'vip_level')->dropDownList($memberLevel) ?>
                <?= $form->field($model, 'realname')->textInput() ?>
                <?= $form->field($model, 'identification_number')->textInput() ?>
                <?= $form->field($model, 'credit_score')->textInput() ?>
                <?= $form->field($model, 'promo_code')->textInput() ?>
                <?= $form->field($model->account, 'usdt_link')->textInput() ?>
                <?= $form->field($model->account, 'alipay_account')->textInput() ?>
                <?= $form->field($model, 'withdraw_switch')->radioList(['1' => '开启', '0' => '关闭']) ?>
                <?= $form->field($model, 'automatic_delivery_switch')->radioList(['1' => '开启', '0' => '关闭']) ?>


                <?php
                $formatJs = <<< JS
var formatRepo = function (repo) {
    if (repo.loading) {
        return repo.text;
    }
    return repo.username;
};
var formatRepoSelection = function (repo) {
    return repo.username || repo.text;
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
                <div id="b_id">
                    <?= $form->field($model, 'b_id', ['options' => ['class' => 'form-group c-md-5']])->widget(\kartik\widgets\Select2::classname(), [
                        'data' => \yii\helpers\ArrayHelper::map(\common\models\backend\Member::find()->asArray()->all(), 'id', 'username'),
                        'options' => ['placeholder' => '请选择后台代理账号'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'ajax' => [
                                'url' => "get-backend-user",
                                'data' => new JsExpression('function(params) { return {q:params.term, page: params.page}; }'),
                                'processResults' => new JsExpression($resultsJs),
                                'cache' => true
                            ],
                            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                            'templateResult' => new JsExpression('formatRepo'),
                            'templateSelection' => new JsExpression('formatRepoSelection'),
                        ],
                    ])->label('后台代理'); ?>
                </div>
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