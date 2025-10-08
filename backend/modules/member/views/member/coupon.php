<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\tea\QuestionsList */
/* @var $form yii\widgets\ActiveForm */

$this->title = '赠送优惠券';
$this->params['breadcrumbs'][] = ['label' => '会员信息', 'url' => ['index']];
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
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'content')->widget(\unclead\multipleinput\MultipleInput::class, [
                        'max' => 10,
                        'columns' => [
                            [
                                'name' => 'key',
                                'title' => '优惠券分类',
                                'type' => \kartik\select2\Select2::class,
                                'enableError' => false,
                                'options' => [
                                    'class' => 'input-priority',
                                    'data' => \yii\helpers\ArrayHelper::map(\common\models\tea\CouponList::find()
                                        ->where(['status' => \common\enums\StatusEnum::ENABLED])
                                        ->select(['id', 'remark'])
                                        ->asArray()
                                        ->all(), 'id', 'remark'),
                                ],
                            ],
                            [
                                'name' => 'value',
                                'title' => '赠送张数',
                                'enableError' => false,
                                'options' => [
                                    'class' => 'input-priority'
                                ]
                            ],
                        ]
                    ]);
                    ?>
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
