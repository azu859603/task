<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\CouponList */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Coupon Lists', 'url' => ['index']];
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
                    'id' => $model->formName(),
                    'enableAjaxValidation' => true,
                    'validationUrl' => \common\helpers\Url::to(['ajax-edit', 'id' => $model['id']]),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'number', ['options' => ['class' => ['chart']]])->textInput()->hint("*红包是赠送金额，加息是加息百分比", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'max', ['options' => ['class' => ['chart']]])->textInput()->hint("*投资金额大于等于此值则可使用", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'valid_date', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'remark', ['options' => ['class' => ['chart']]])->textInput()->hint("*备注是在发布产品的时候方便选择", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'type', ['options' => ['class' => ['chart']]])->radioList(\common\models\tea\CouponList::$typeExplain) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList(['1' => '启用', '0' => '禁用'])->hint("*若禁用则关联的项目不再赠送此卡，已赠送出去的可以继续使用", ['style' => 'color:red']) ?>
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
