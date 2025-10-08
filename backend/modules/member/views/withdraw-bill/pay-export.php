<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\member\WithdrawBill;
use common\helpers\DateHelper;


function checkUrl($url)
{
    $first = mb_substr($url, 0, 1);
    if ($first == "/") {
        $url = Yii::$app->request->getHostInfo() . $url;
    }
    return $url;
}

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '支付导出';
$this->params['breadcrumbs'][] = ['label' => '提现审核', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'hover' => true,
                    "options" => ["class" => "grid-view", "style" => "overflow:auto", "id" => "grid"],
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                        ],
                        'sn',
                        [
                            'attribute' => 'withdraw_money',
                            'headerOptions' => ['width' => '150px'],
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'type',
                            'format' => 'raw',
                            'value' => function ($model) {
                                if ($model->type == WithdrawBill::ALIPAY_ACCOUNT) {
                                    return '支付宝户主：' . $model->account->alipay_user_name . "<br/>支付宝账号：" . $model->account->alipay_account;
                                } elseif ($model->type == WithdrawBill::BANK_CARD) {
                                    return '卡号：' . $model->account->bank_card . "<br/>户主：" . $model->member->realname . "<br/>开户行：" . $model->account->bank_address;
                                } elseif ($model->type == WithdrawBill::WECHAT_ACCOUNT_URL) {
                                    return '<a target="_blank" href="' . checkUrl($model->account->wechat_account_url) . '">微信收款码链接</a>';
                                } elseif ($model->type == WithdrawBill::ALIPAY_ACCOUNT_URL) {
                                    return '<a target="_blank" href="' . checkUrl($model->account->alipay_account_url) . '">支付宝收款码链接</a>';
                                }
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status', WithdrawBill::$statusExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return '<span class="label label-' . WithdrawBill::$statusColorExplain[$model->status] . '">' . WithdrawBill::$statusExplain[$model->status] . '</span>';
                            },
                        ],
                        [
                            'attribute' => 'created_at',
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'convertFormat' => true,
                                'name' => 'created_at',
                                'attribute' => 'created_at',
                                'hideInput' => true,
                                'options' => ['placeholder' => '请选择时间段...', 'class' => 'form-control'],
                                'pluginOptions' => [
                                    'timePicker' => true,
                                    'locale' => [
                                        'format' => 'Y-m-d',
                                        'separator' => '~'
                                    ],
                                    'opens' => 'left'
                                ],
                                'pluginEvents' => [
                                    "cancel.daterangepicker" => "function(ev, picker) {
                            $(picker.element[0].children[1]).val('');
                            $(picker.element[0].children[0].children[1]).val('').trigger('change');
                        }"
                                ]
                            ]),
                            'value' => function ($model) {
                                return DateHelper::dateTime($model->created_at);
                            },
                        ],
                        [
                            'attribute' => 'updated_at',
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'convertFormat' => true,
                                'name' => 'updated_at',
                                'attribute' => 'updated_at',
                                'hideInput' => true,
                                'options' => ['placeholder' => '请选择时间段...', 'class' => 'form-control'],
                                'pluginOptions' => [
                                    'timePicker' => true,
                                    'locale' => [
                                        'format' => 'Y-m-d',
                                        'separator' => '~'
                                    ],
                                    'opens' => 'left'
                                ],
                                'pluginEvents' => [
                                    "cancel.daterangepicker" => "function(ev, picker) {
                            $(picker.element[0].children[1]).val('');
                            $(picker.element[0].children[0].children[1]).val('').trigger('change');
                        }"
                                ]
                            ]),
                            'value' => function ($model) {
                                return DateHelper::dateTime($model->updated_at);
                            },
                        ],
                    ],
                    'panel' => [
                        'heading' => false,
                        'before' => '<div class="box-header pull-left"><i class="fa fa-fw fa-sun-o"></i><h3 class="box-title">' . $this->title . '</h3></div>',
                        'footer' => false,
                        'after' => '<div class="pull-left" style="margin-top: 8px">{summary}</div><div class="kv-panel-pager pull-right">{pager}</div><div class="clearfix"></div>',
                    ],
                    'panelFooterTemplate' => '{footer}<div class="clearfix"></div>',
                    'toolbar' => [
                        '{export}'
                    ],
                    'exportConfig' => [
                        GridView::HTML => [
                            'filename' => '支付导出_' . date('Y-m-d'),
                            'mime' => 'text/html',
                        ]
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs('
$(".bulk_forbid").on("click", function (e) {
    e.preventDefault();
    var keys = $("#grid").yiiGridView("getSelectedRows");
    if(keys.length < 1) {
        return rfError("", "没有选中任何项");
    }
    var href = $(this).attr("href");
    window.location.href = href + "?ids=" + keys.join();
});
');
?>
