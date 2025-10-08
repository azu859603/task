<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\tea\InvestmentProject;
use common\models\tea\InvestmentBill;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '购买列表';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                </div>
            </div>
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'hover' => true,
                    'options' => ["class" => "grid-view", "style" => "overflow:auto", "id" => "grid"],
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        // 若要全选则关闭上面打开下面的代码
                        [
                            'class' => '\kartik\grid\CheckboxColumn',
                            'rowSelectedClass' => GridView::TYPE_INFO,
                            'visible' => true,
                        ],

                        [
                            'attribute' => 'id',
                            'headerOptions' => ['width' => '50px'],
                        ],

                        [
                            'attribute' => 'category',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'category', InvestmentProject::$categoryArray, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return InvestmentProject::$categoryArray[$model->category];
                            },
                            'headerOptions' => ['width' => '89px'],
                        ],

                        [
                            'label' => '会员信息',
                            'attribute' => 'member_id',
                            'filter' => Html::activeTextInput($searchModel, 'member_id', [
                                    'class' => 'form-control',
                                    'placeholder' => '会员账号'
                                ]
                            ),
                            'value' => function ($model) {
                                if (!empty($model->member->remark)) {
                                    if (mb_strlen($model->member->remark) > 10) {
                                        $remark = mb_substr($model->member->remark, 0, 10, 'utf-8') . "..";
                                    } else {
                                        $remark = $model->member->remark;
                                    }
                                } else {
                                    $remark = "(暂无)";
                                }
                                $realname = !empty($model->member->realname) ? $model->member->realname : "(暂无)";
                                return Html::a(
                                    "账号：" . $model->member->mobile . '<br>' .
                                    "昵称：" . $model->member->nickname . '<br>' .
                                    "姓名：" . $realname . '<br>' .
                                    "备注：" . $remark . '<br>',
                                    ['/member/member/view', 'id' => $model->member->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],
                        [
                            'label' => '产品信息',
                            'attribute' => 'investmentProject.title',
                            'filter' => Html::activeTextInput($searchModel, 'investmentProject.title', [
                                    'class' => 'form-control',
                                    'placeholder' => '产品名称'
                                ]
                            ),
                            'value' => function ($model) {
                                if (!empty($model->investmentProject->title)) {
                                    if (mb_strlen($model->investmentProject->title) > 10) {
                                        $title = mb_substr($model->investmentProject->title, 0, 10, 'utf-8') . "..";
                                    } else {
                                        $title = $model->investmentProject->title;
                                    }
                                } else {
                                    $title = "(无)";
                                }
                                $jx_income = !empty($model->cj->coupon) ? $model->cj->coupon->number : '0.00';
                                return "名称：" . $title . '<br>' .
                                    "期限：" . $model->investmentProject->deadline . '天<br>' .
                                    "项目收益百分比：" . $model->investmentProject->income . '<br>' .
                                    "会员收益百分比：" . \common\helpers\BcHelper::sub($model->add_income, $jx_income) . '<br>' .
                                    "加息收益百分比：" . $jx_income;
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],
                        [
                            'label' => '投资金额(' . $sum_investment_amount . ')',
                            'attribute' => 'investment_amount',
                            'value' => function ($model) {
                                if (!empty($model->ch)) {
                                    return $model->investment_amount . '<br>' . '(满减' . $model->ch->coupon->number . '元)';
                                } else {
                                    return $model->investment_amount;
                                }

                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '110px'],
                            'contentOptions' => [
                                'style' => 'text-align:center',
                            ],
                        ],
                        [
                            'label' => '已获收益(' . $sum_income_amount_all . ')',
                            'attribute' => 'income_amount_all',
                            'value' => function ($model) {
                                return $model->income_amount_all . '<br>' .
                                    Html::a('【收益记录】', "/backend/member/credits-log/money?SearchModel%5Bmap_id%5D=" . $model->id . "&SearchModel%5Bmember_id%5D=" . $model->member->mobile);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '110px'],
                            'contentOptions' => [
                                'style' => 'text-align:center',
                            ],
                        ],
                        [
                            'attribute' => 'income_amount',
                            'value' => function ($model) {
                                if ($model->income_amount > 0) {
                                    return '<span class="label label-success">收益：' . $model->income_amount . '</span>';
                                } else {
                                    return '<span class="label label-danger">暂无结算</span>';
                                }
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '90px'],
                        ],

                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status', InvestmentBill::$statusArray, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return '<span class="label label-' . InvestmentBill::$statusColor[$model->status] . '">' . InvestmentBill::$statusArray[$model->status] . '</span>';
                            },
                        ],
                        ['class' => '\kartik\grid\EditableColumn', 'attribute' => 'remark', 'format' => 'raw'],
                        [
                            'label' => '收货信息',
                            'attribute' => 'send_status',
                            'filter' => Html::activeDropDownList($searchModel, 'send_status', InvestmentBill::$sendStatusArray, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'format' => 'raw',
                            'value' => function ($model) {
                                if ($model->send_status == 0) {
                                    return "不赠送";
                                } else {

                                    $name = !empty($model['send_name']) ? $model['send_name'] : "无";
                                    $mobile = !empty($model['send_mobile']) ? $model['send_mobile'] : "无";
                                    $address = !empty($model['send_address']) ? $model['send_address'] : "无";
                                    $send_remark = !empty($model['send_remark']) ? $model['send_remark'] : "无";
                                    return "收件人：" . '<br>' . $name . '<br>' .
                                        "联系电话：" . '<br>' . $mobile . '<br>' .
                                        "收货地址：" . '<br>' . $address . '<br>' .
                                        "单号：" . '<br>' . $send_remark . '<br>' .
                                        "状态：" . '<br>' . '<span class="label label-' . InvestmentBill::$statusColorExplain[$model->send_status] . '">' . InvestmentBill::$sendStatusArray[$model->send_status] . '</span>';

                                }
                            }
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
                                return \common\helpers\DateHelper::dateTime($model->created_at);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '102px'],
                        ],
                        [
                            'headerOptions' => ['width' => '102px'],
                            'attribute' => 'next_time',
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'convertFormat' => true,
                                'name' => 'next_time',
                                'attribute' => 'next_time',
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
                                return \common\helpers\DateHelper::dateTime($model->next_time);
                            },
                        ],
                        [
                            'headerOptions' => ['width' => '102px'],
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
                                return \common\helpers\DateHelper::dateTime($model->updated_at);
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{recharge} {jiesuan} {daoqi} {zhuantou} {tingjie} {kuaidi}',
                            'buttons' => [
                                'recharge' => function ($url, $model, $key) {
                                    return Html::linkButton(['/member/member/recharge', 'id' => $model->member_id], '赠送', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                        'class' => 'btn btn-primary btn-sm',
                                    ]);
                                },
                                'jiesuan' => function ($url, $model, $key) {
                                    if ($model->income_amount > 0) {
                                        return Html::linkButton(['jiesuan', 'id' => $model->id], "结算", [
                                            'class' => 'btn btn-success btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '点击结算后收益将发放到会员余额,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }

                                },
                                'daoqi' => function ($url, $model, $key) {
                                    if ($model->status < 3) {
                                        return Html::linkButton(['daoqi', 'id' => $model->id], "结束", [
                                            'class' => 'btn btn-warning btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '点击结束后,该产品将不再产生收益并退还本金,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }

                                },

                                'zhuantou' => function ($url, $model, $key) {
                                    if ($model->status > 2) {
                                        return Html::linkButton(['zhuantou', 'member_id' => $model->member_id, 'investment_amount' => $model->investment_amount], '转购', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class' => 'btn btn-info btn-sm',
                                        ]);
                                    }

                                },

                                'tingjie' => function ($url, $model, $key) {
                                    if ($model->status != 4) {
                                        return Html::linkButton(['tingjie', 'id' => $model->id], "停结", [
                                            'class' => 'btn btn-danger btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '点击停结后,该产品已产生的收益将清零,不再产生收益并且本金也不予退还,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }
                                },

                                'kuaidi' => function ($url, $model, $key) {
                                    if ($model->send_status > 0) {
                                        return Html::linkButton(['kuaidi', 'id' => $model->id], '快递', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class' => 'btn btn-info btn-sm',
                                        ]);
                                    }
                                },
                            ],
                        ],
                    ],
                    'panel' => [
                        'heading' => false,
                        'before' => '<div class="box-header pull-left"><i class="fa fa-fw fa-sun-o"></i><h3 class="box-title">数据管理</h3></div>',
                        'footer' => false,
                        'after' => '<div class="pull-left" style="margin-top: 8px">{summary}</div><div class="kv-panel-pager pull-right">{pager}</div><div class="clearfix"></div>',
                    ],
                    'panelFooterTemplate' => '{footer}<div class="clearfix"></div>',
                    'toolbar' => [
                        '<div class="pull-left">'
                        . Html::a('导出订单', Url::to(['export']), ['class' => 'btn btn-info', "id" => "bulk_forbid"])
                        . Html::a('导入订单', Url::to(['import']), ['class' => 'btn btn-warning', 'data-toggle' => 'modal', 'data-target' => '#ajaxModal',])
                        . Html::a('<i class="glyphicon  glyphicon-ok-circle"></i>批量结算', Url::to('pass-all'), ['class' => 'btn btn-success', 'id' => 'bulk_forbid'])
                        . '</div>',
                        '{toggleData}',
                        '{export}'
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
<!-- 若全选打开下面代码-->
<?php
$this->registerJs('
$("#bulk_forbid").on("click", function (e) {
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
