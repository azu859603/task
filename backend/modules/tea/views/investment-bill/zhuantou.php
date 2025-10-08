<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\common\ImgDetails */
/* @var $form yii\widgets\ActiveForm */

$this->title = '转购';
$this->params['breadcrumbs'][] = ['label' => '转购', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
    #ajaxModal {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .modal-dialog {
        width: 50%;
    }

    .modal-content {
        width: 100%;
    }
</style>

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
                    <?= $form->field($model, 'investment_amount', ['options' => ['class' => ['chart']]])->textInput(['maxlength' => true]) ?>
                    <div style="display: none">
                        <?= $form->field($model, 'member_id', ['options' => ['class' => ['chart']]])->textInput(['maxlength' => true]) ?>
                    </div>
                    <?= $form->field($model, 'pid', ['options' => ['class' => ['chart']]])->dropDownList(\yii\helpers\ArrayHelper::map(\common\models\tea\InvestmentProject::find()->where(['status' => 1])->asArray()->all(), 'id', 'title')) ?>
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
