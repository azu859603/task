<?php

use common\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\member\RechargeDetail */
/* @var $form yii\widgets\ActiveForm */

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'Recharge Details', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$category = \yii\helpers\ArrayHelper::map(\common\models\member\RechargeCategory::find()->asArray()->all(), 'id', 'title');
$category[10000] = "USDT-TRC20";
$category[10001] ="线下充值-银行卡";
$category[10002] ="线下充值-支付宝";
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
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">
                    <?= $form->field($model, 'title', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'pid', ['options' => ['class' => ['chart']]])->dropDownList($category, ["onchange" => "checkOption(this)"]) ?>
                    <div id="online" style="display: none">
                        <?= $form->field($model, 'code', ['options' => ['class' => ['chart']]])->textInput() ?>
                    </div>
                    <div id="usdt" style="display: none">
                        <?= $form->field($model, 'usdt_trc20', ['options' => ['class' => ['chart']]])->textInput() ?>
                        <?= $form->field($model, 'exchange_rate', ['options' => ['class' => ['chart']]])->textInput() ?>
                    </div>
                    <div id="offline">
                        <?= $form->field($model, 'payee', ['options' => ['class' => ['chart']]])->textInput() ?>
                        <?= $form->field($model, 'bank_name', ['options' => ['class' => ['chart']]])->textInput() ?>
                        <?= $form->field($model, 'bank_card', ['options' => ['class' => ['chart']]])->textInput() ?>
                        <?= $form->field($model, 'help', ['options' => ['class' => ['chart']]])->widget(\common\widgets\ueditor\UEditor::class, []) ?>
                    </div>
                    <div id="zfb" style="display: none">
                        <?= $form->field($model, 'zfb_name', ['options' => ['class' => ['chart']]])->textInput() ?>
                        <?= $form->field($model, 'zfb_number', ['options' => ['class' => ['chart']]])->textInput() ?>
                    </div>
                    <?= $form->field($model, 'min_money', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'max_money', ['options' => ['class' => ['chart']]])->textInput() ?>
                    <?= $form->field($model, 'sort', ['options' => ['class' => ['chart']]])->textInput()->hint("*越小越靠前", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'status', ['options' => ['class' => ['chart']]])->radioList([1 => '启用', 0 => '禁用']) ?>
                    <?= $form->field($model, 'remark', ['options' => ['class' => ['chart']]])->textInput() ?>

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


<script type="text/javascript">
    init();
    function init(){
        var myselect=document.getElementById("rechargedetail-pid");
        var index=myselect.selectedIndex ;
        var value = myselect.options[index].value;
        var online = document.getElementById('online')
        var offline = document.getElementById('offline')
        var usdt = document.getElementById('usdt')
        var zfb = document.getElementById('zfb')
        console.log(value);
        if (value == 10001) {
            offline.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }else if(value == 10000) {
            usdt.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            offline.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }else if(value == 10002) {
            zfb.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            offline.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
        }else {
            online.setAttribute('style', '')
            offline.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }
    }

    function checkOption(that) {
        console.log(that.value);
        var online = document.getElementById('online')
        var offline = document.getElementById('offline')
        var usdt = document.getElementById('usdt')
        var zfb = document.getElementById('zfb')
        if (that.value == 10001) {
            offline.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }else if(that.value == 10000) {
            usdt.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            offline.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }else if(that.value == 10002) {
            zfb.setAttribute('style', '')
            online.setAttribute('style', 'display:none;')
            offline.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
        }else {
            online.setAttribute('style', '')
            offline.setAttribute('style', 'display:none;')
            usdt.setAttribute('style', 'display:none;')
            zfb.setAttribute('style', 'display:none;')
        }
    }
</script>