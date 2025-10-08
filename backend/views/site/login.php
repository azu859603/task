<?php
$this->title = Yii::$app->params['adminTitle'];

use common\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\captcha\Captcha;

?>
<style>
    body::-webkit-scrollbar { /*滚动条整体样式*/
        width: 0px; /*高宽分别对应横竖滚动条的尺寸*/
        height: 1px;
    }

    #user-login-box {
        margin-top: -9%;
        position: absolute;
    }

    @media screen and (max-width: 1000px) {
        #user-login-box {
            margin-top: 42%;
            position: absolute;
        }
    }


</style>
<body class="hold-transition login-page" id="particles-js"
      style="display: flex;justify-content: center;align-items: center">

<div class="login-box" id="user-login-box">
    <div class="login-logo">
        <?= Html::encode(Yii::$app->params['adminTitle']); ?>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg">欢迎登录</p>
        <?php $form = ActiveForm::begin([
            'id' => 'login-form'
        ]); ?>
        <?= $form->field($model, 'username', [
            'template' => '<div class="form-group has-feedback">{input}<span class="glyphicon glyphicon-user form-control-feedback"></span></div>{hint}{error}'
        ])->textInput(['placeholder' => '用户名'])->label(false); ?>
        <?= $form->field($model, 'password', [
            'template' => '<div class="form-group has-feedback">{input}<span class="glyphicon glyphicon-lock form-control-feedback"></span></div>{hint}{error}'
        ])->passwordInput(['placeholder' => '密码'])->label(false); ?>

        <div class="google_code_container" style="display: none">
            <?= $form->field($model, 'google_code', [
                'template' => '<div class="form-group has-feedback">{input}<span class="glyphicon glyphicon-off form-control-feedback"></span></div>{hint}{error}'
            ])->textInput(['placeholder' => '谷歌验证码'])->label(false); ?>
        </div>

        <?php if ($model->scenario == 'captchaRequired') { ?>
            <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
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
        <?php } ?>
        <?= $form->field($model, 'rememberMe')->checkbox() ?>
        <div class="form-group">
            <?= Html::submitButton('立即登录', ['class' => 'btn btn-primary btn-block', 'name' => 'login-button']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        <div class="social-auth-links text-center">
            <p><?= Html::encode(Yii::$app->debris->config('web_copyright')); ?></p>
        </div>
    </div>
    <!-- /.login-box-body -->
</div>
<!-- /.login-box -->
<?php
$this->registerJsFile('@web/resources/plugins/ycmc/particles.js');
$this->registerJsFile('@web/resources/plugins/ycmc/app.js');
?>
</body>
<script>
    $('#loginform-username').blur(function () {
        let username = $('#loginform-username').val();
        $.ajax({
            type: "post",
            url: '/backend/site/google',
            data: {username: username},
            success: function (data) {
                if (data.code == 200) {
                    $('.google_code_container').show();
                } else {
                    $('.google_code_container').hide();
                }
            }
        });
    });
</script>