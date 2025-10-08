<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\InvestmentProject */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Investment Projects', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$ch_data = \yii\helpers\ArrayHelper::map(\common\models\tea\CouponList::find()->where(['type' => 1])->select(['id', 'remark'])->asArray()->all(), 'id', 'remark');
$ch_data[0] = '不赠送';
$cj_data = \yii\helpers\ArrayHelper::map(\common\models\tea\CouponList::find()->where(['type' => 2])->select(['id', 'remark'])->asArray()->all(), 'id', 'remark');
$cj_data[0] = '不赠送';
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
                    <?= $form->field($model, 'category', ['options' => ['class' => ['chart']]])->dropDownList(\common\models\tea\InvestmentProject::$categoryArray) ?>
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'describe', ['options' => ['class' => ['chart']]])->textarea() ?>
                    <?= $form->field($model, 'home_show_switch', ['options' => ['class' => ['chart']]])->radioList(['1' => '展示', '0' => '隐藏']) ?>
                    <?= $form->field($model, 'project_img', ['options' => ['class' => ['chart']]])->widget(\common\widgets\webuploader\Files::class, [
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
                    <?= $form->field($model, 'all_investment_amount', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：万元", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'schedule', ['options' => ['class' => ['chart']]])->textInput()->hint("*百分比，产品规模/已购金额", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'least_amount', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：元", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'most_amount', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：元，若输入0则不限制", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'limit_times', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：次，若输入0则不限制", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'investment_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'deadline', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：天", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'income', ['options' => ['class' => ['chart']]])->textInput()->hint("*百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'commission_one', ['options' => ['class' => ['chart']]])->textInput()->hint("*百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'commission_two', ['options' => ['class' => ['chart']]])->textInput()->hint("*百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'gift_method', ['options' => ['class' => ['chart']]])->radioList(\common\models\tea\InvestmentProject::$giftMethod) ?>
                    <?= $form->field($model, 'gift_amount', ['options' => ['class' => ['chart']]])->textInput()->hint("*若赠送红包,请填入此值，不赠送则填入0，赠送金额=份数X此值", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'gift_amount_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DatePicker::class, [
                        'options' => ['value' => !empty($model->gift_amount_time) ? date("Y-m-d", $model->gift_amount_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ])->hint("*当前注册时间在此之后，才赠送红包，若为空则不限制", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'return_method', ['options' => ['class' => ['chart']]])->radioList(\common\models\tea\InvestmentProject::$giftMethod) ?>
                    <?= $form->field($model, 'return_percentage', ['options' => ['class' => ['chart']]])->textInput()->hint("*若赠送返现,请填入此值，不赠送则填入0，单位：百分比，计算方式：返现=投资金额X此值/100", ['style' => 'color:red']) ?>

                    <?= $form->field($model, 'lottery_number', ['options' => ['class' => ['chart']]])->textInput()->hint("*若赠送抽奖次数,请填入此值，不赠送则填入0，赠送次数=份数X此值", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'spike_type', ['options' => ['class' => ['chart']]])->radioList(['1' => '开启', '0' => '关闭'])->hint("*若开启秒杀活动，则只能在秒杀活动时间段内进行产品购买", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'spike_start_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                        'options' => ['value' => !empty($model->spike_start_time) ? date("Y-m-d H:i:s", $model->spike_start_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ])->hint("*若开启秒杀活动，请填入开始时间，若关闭秒杀活动，此值可不管", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'spike_stop_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DateTimePicker::class, [
                        'options' => ['value' => !empty($model->spike_stop_time) ? date("Y-m-d H:i:s", $model->spike_stop_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ])->hint("*若开启秒杀活动，请填入结束时间，若关闭秒杀活动，此值可不管", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'prize_type', ['options' => ['class' => ['chart']]])->radioList(['1' => '赠送', '0' => '不赠送']) ?>
                    <?= $form->field($model, 'project_detail', ['options' => ['class' => ['chart']]])->widget(\common\widgets\ueditor\UEditor::class, []) ?>
                    <?= $form->field($model, 'integral_percentage', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：百分比，计算方式：可获积分=购买金额X此值/100", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'parent_integral_percentage', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：百分比，计算方式：可获积分=购买金额X此值/100", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'my_get_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'one_get_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'vip_level', ['options' => ['class' => ['chart']]])->textInput()->hint("*请填入对应购买等级，用户等级大于等于此值则可购买，如：0，1，2。。。", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'increase_status', ['options' => ['class' => ['chart']]])->radioList(['1' => '开启', '0' => '关闭']) ?>
                    <?= $form->field($model, 'increase_times', ['options' => ['class' => ['chart']]])->textInput([]) ?>
                    <?= $form->field($model, 'experience_multiple', ['options' => ['class' => ['chart']]])->textInput([]) ?>
                    <?= $form->field($model, 'project_superior_rebate', ['options' => ['class' => ['chart']]])->textInput([])->hint("*若赠送上级返佣奖励,请填入此值，不赠送则填入0，赠送金额=份数X此值", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'parent_lottery_number', ['options' => ['class' => ['chart']]])->textInput()->hint("*若赠送上级抽奖次数,请填入此值，不赠送则填入0，赠送次数=份数X此值", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'project_superior_rebate_time', ['options' => ['class' => ['chart']]])->widget(\kartik\widgets\DatePicker::class, [
                        'options' => ['value' => !empty($model->project_superior_rebate_time) ? date("Y-m-d", $model->project_superior_rebate_time) : ""],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'timePicker' => true,
                        ]
                    ])->hint("*下级注册时间在此之后，才赠送上级返佣奖励，若为空则不限制", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'ch_id', ['options' => ['class' => ['chart']]])->dropDownList($ch_data) ?>
                    <?= $form->field($model, 'cj_id', ['options' => ['class' => ['chart']]])->dropDownList($cj_data) ?>
                    <?= $form->field($model, 'send_gift_switch', ['options' => ['class' => ['chart']]])->radioList(['1' => '赠送', '0' => '不赠']) ?>
                    <?= $form->field($model, 'project_status', ['options' => ['class' => ['chart']]])->radioList(['1' => '启用', '0' => '禁用']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">保存</button>
                        <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
