<?php

use common\helpers\BcHelper;
use common\helpers\Html;
use common\models\member\Member;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model common\models\dj\SellerAvailableList */
/* @var $form yii\widgets\ActiveForm */

$this->title = '添加订单';
$this->params['breadcrumbs'][] = ['label' => 'Seller Available Lists', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"></h3>
            </div>
            <div>
                <table class="table table-hover text-center">
                    <tbody>
                    <tr>
                        <td>短剧名</td>
                        <td>是否上架</td>
                        <td>售价</td>
                        <td>代销价</td>
                        <td>收益</td>
                    </tr>
                    <?php
                    $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
                    $lang = Yii::$app->request->get('lang', $default_lang);
                    $sj_money = 0;
                    $dx_money = 0;
                    $sy_money = 0;
                    $memberInfo = Member::find()->where(['id' => $seller_id])->with(['sellerLevel'])->one();
                    foreach ($ids as $id) {
                        $shortPlaysList = \common\models\dj\ShortPlaysList::find()->where(['id' => $id])
                            ->with([
                                'sellerAvailableList' => function ($query) use ($seller_id) {
                                    $query->where(['member_id' => $seller_id]);
                                },
                                'translation' => function ($query) use ($lang) {
                                    $query->where(['lang' => $lang]);
                                },
                            ])
                            ->asArray()
                            ->one();
                        $sj_money = BcHelper::add($sj_money, $shortPlaysList['amount']);
                        if (!empty($shortPlaysList['sellerAvailableList'])) {
                            $sj_str = " <td>已上架</td>";
                            $sy_str = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit, 100, 4), $shortPlaysList['amount']);
                            $dx_str = BcHelper::sub($shortPlaysList['amount'], $sy_str);
                        } else {
                            $sj_str = " <td>未上架</td>";
                            $sy_str = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit_rebate, 100, 4), $shortPlaysList['amount']);
                            $dx_str = BcHelper::sub($shortPlaysList['amount'], $sy_str);
                        }
                        $sy_money = BcHelper::add($sy_money, $sy_str);
                        $dx_money = BcHelper::add($dx_money, $dx_str);
                        echo "<tr> 
                                <td>" . $shortPlaysList['translation']['title'] . "</td>" .
                            $sj_str
                            . "<td>" . $shortPlaysList['amount'] . "</td>"
                            . "<td>" . $dx_str . "</td>"
                            . "<td>" . $sy_str . "</td>
                              </tr>";
                    }
                    ?>
                    <tr>
                        <td></td>
                        <td></td>
                        <td><?= $sj_money ?></td>
                        <td><?= $dx_money ?></td>
                        <td><?= $sy_money ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="box-body">

                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'template' => "<div class='col-sm-3 text-right'>{label}</div><div class='col-sm-9'>{input}\n{hint}\n{error}</div>",
                    ],
                ]); ?>
                <div class="col-sm-12">

                    <?php
                    $formatJs = <<< JS
var formatRepo = function (repo) {
    if (repo.loading) {
        return repo.text;
    }
    return repo.mobile;
};
var formatRepoSelection = function (repo) {
    return repo.mobile || repo.text;
}
JS;
                    $this->registerJs($formatJs, \yii\web\View::POS_HEAD);

                    $resultsJs = <<< JS
function (data, params) {
    params.page = params.page || 1;
    return {
        results: data.data,
        pagination: {
            more: (params.page * 20) < data.message
        }
    };
}
JS;
                    ?>

                    <?= $form->field($model, 'member_id', ['options' => ['class' => ['chart']]])->textInput()->hint("*填入虚拟买家数量，当前卖家的虚拟买家有".$virtual_count."位", ['style' => 'color:red']) ?>

                    <?= $form->field($model, 'start_time', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：小时，开始时间，填入0，表示当前时间开始，填入1，1小时后开始", ['style' => 'color:red']) ?>
                    <?= $form->field($model, 'stop_time', ['options' => ['class' => ['chart']]])->textInput()->hint("*单位：小时，订单生成时间段，填入如：0-24，则表示当前时间至24小时内", ['style' => 'color:red']) ?>

                </div>
                <div class="form-group">
                    <div class="col-sm-12 text-center">
                        <button class="btn btn-primary" type="submit">提交</button>
                        <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
