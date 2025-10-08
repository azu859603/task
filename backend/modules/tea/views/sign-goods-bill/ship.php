<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\member\RechargeBill */
/* @var $form yii\widgets\ActiveForm */

$this->title = '发货';
$this->params['breadcrumbs'][] = ['label' => '订单列表', 'url' => ['index']];
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
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'remark')->textarea(['rows' => '6'])->hint("*请输入发货快递单号", ['style' => 'color:red']) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">

                        <button class="btn btn-primary" type="submit">保存</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
