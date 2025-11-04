<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */

$this->title = '导出';
$this->params['breadcrumbs'][] = ['label' => '订单列表', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$level_array = \yii\helpers\ArrayHelper::map(\common\models\member\Level::find()->asArray()->all(), 'level', 'name');
$level_array[0] = "全部";
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
                    <?= $form->field($model, 'created_at', ['options' => ['class' => ['chart']]])->widget(\kartik\daterange\DateRangePicker::classname(),[
                        'readonly' => 'readonly',
                        'options' => ['placeholder' => '请选择时间段...', 'class' => 'form-control'],
                        'pluginOptions' => [
                            'timePicker' => true,
                            'locale' => [
                                'separator' => '~'
                            ],
                            'opens' => 'left'
                        ],
                    ]) ?>
                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">

                        <button class="btn btn-primary" type="submit">导出</button>
                        <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
