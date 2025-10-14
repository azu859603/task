<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\member\WithdrawBill */
/* @var $form yii\widgets\ActiveForm */

$this->title = '拒绝';
$this->params['breadcrumbs'][] = ['label' => '任务列表', 'url' => ['index']];
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
                    'id' => $model->formName(),
                    'enableAjaxValidation' => true,
                    'class' => 'form-horizontal',
                    'validationUrl' => Url::to(['no-pass', 'id' => $model->id, 'status' => 3]),
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'remark')->textarea(['rows' => '6']) ?>
                    <?= $form->field($model, 'status')->radioList([3 => '已驳回'])?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">

                        <button id="button" class="btn btn-primary" type="submit">保存</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>

<script>
    $("#button").click(function () {
        console.log(111111111111)
        $(this).submit();
        $(this).prop("disabled", true); // 禁用按钮

    });
</script>
