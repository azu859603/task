<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

/* @var $model \frontend\models\SignupForm */

use common\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = '注册';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Please fill out the following fields to signup:</p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

            <?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>


            <?= $form->field($model, 'password')->passwordInput() ?>
            <?= $form->field($model, 'code')->passwordInput() ?>
            <button class="form-group field-signupform-code btn-success">发送</button>


            <?= $form->field($model, 'verifyCode')->widget(\yii\captcha\Captcha::class, [
                'template' => '<div class="row"><div class="col-sm-7">{input}</div><div class="col-sm-5">{image}</div></div>',
                'imageOptions' => [
                    'alt' => '点击换图',
                    'title' => '点击换图',
                    'style' => 'cursor:pointer'
                ],
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => '验证码',
                ],
            ])->label(false); ?>


            <div class="form-group">
                <?= Html::submitButton('Signup', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
