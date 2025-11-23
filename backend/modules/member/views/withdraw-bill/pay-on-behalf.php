<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\member\WithdrawBill */
/* @var $form yii\widgets\ActiveForm */

$this->title = '代付';
$this->params['breadcrumbs'][] = ['label' => '提现记录', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span
                class="sr-only">关闭</span>
    </button>
    <h4 class="modal-title"><?= $this->title ?></h4>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                    ]
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'pay_type')->dropDownList(\common\models\member\WithdrawBill::$payTypeExplain)->hint("*点击提交成功后将会给用户卡里加钱，请谨慎操作", ['style' => 'color:red']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">提交</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
