<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\member\WithdrawBill;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '提现订单';
$this->params['breadcrumbs'][] = $this->title;
?>
    <style>
        #remark::-webkit-input-placeholder {
            color: red;
            font-size: 13px;
        }
    </style>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body table-responsive">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'hover' => true,
                        'options' => ["class" => "grid-view", "style" => "overflow:auto", "id" => "grid"],
                        'tableOptions' => ['class' => 'table table-hover'],
                        'columns' => [
                            [
                                'class' => 'yii\grid\SerialColumn',
                                'visible' => false,
                            ],
                            // 若要全选则关闭上面打开下面的代码
//                            [
//                                'class' => '\kartik\grid\CheckboxColumn',
//                                'rowSelectedClass' => GridView::TYPE_INFO,
//                                'visible' => true,
//                            ],

                            'id',
                            [
                                'headerOptions' => ['width' => '216px'],
                                'label' => '关联用户',
                                'attribute' => 'member_id',
                                'filter' => Html::activeTextInput($searchModel, 'member_id', [
                                        'class' => 'form-control',
                                        'placeholder' => '输入账号查询'
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
                                    $nickname = !empty($model->member->nickname) ? $model->member->nickname : "(暂无)";
                                    return Html::a(
                                        "账号：" . $model->member->mobile . '<br>' .
                                        "昵称：" . Html::encode($nickname) . '<br>' .
//                                        "姓名：" . Html::encode($realname) . '<br>' .
                                        "备注：" . $remark . '<br>',
                                        ['/member/member/view', 'id' => $model->member->id],
                                        [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                        ]);
                                },
                                'format' => 'raw',
                            ],
                            'sn',
                            [
                                'label' => '提现金额(' . $sum_withdraw_money . ')',
                                'headerOptions' => ['class' => 'col-md-2'],
                                'attribute' => 'withdraw_money'
                            ],
                            [
                                'headerOptions' => ['class' => 'col-md-1'],
                                'attribute' => 'real_withdraw_money',
                                'value' => function ($model) {
                                    if ($model->type == 6) {
                                        return $model->real_withdraw_money . "(USDT)";
                                    } else {
                                        return $model->real_withdraw_money;
                                    }
                                }
                            ],
                            [
                                'attribute' => 'type',
                                'format' => 'raw',
                                'filter' => Html::activeDropDownList($searchModel, 'type', WithdrawBill::$typeExplain, [
                                        'prompt' => '全部',
                                        'class' => 'form-control'
                                    ]
                                ),
                                'value' => function ($model) {
                                    $data = WithdrawBill::$typeExplain;
//                                    if ($model->type == WithdrawBill::ALIPAY_ACCOUNT || $model->type == WithdrawBill::BANK_CARD) {
//                                        return '<a href="/backend/member/account/view?id=' . $model->id . '" data-toggle="modal" data-target="#ajaxModal">' . $data[$model->type] . '</a>';
//                                    }
                                    if ($model->type == WithdrawBill::WECHAT_ACCOUNT_URL) {
                                        return '<a href="' . $model->account->wechat_account_url . '" data-fancybox="gallery">' . $data[$model->type] . '</a>';
                                    } elseif ($model->type == WithdrawBill::ALIPAY_ACCOUNT_URL) {
                                        return '<a href="' . $model->account->alipay_account_url . '" data-fancybox="gallery">' . $data[$model->type] . '</a>';
                                    } elseif ($model->type == WithdrawBill::USDT_TRC20) {
                                        return $data[$model->type] . "<br>USDT-TRC20地址：" . $model->account->usdt_link;
                                    } elseif ($model->type == WithdrawBill::PLATFORM_ACCOUNT) {
                                        return $data[$model->type] . "<br>账号：" . $model->account->platform_account;
                                    } elseif ($model->type == WithdrawBill::GCASH_ACCOUNT) {
                                        return $data[$model->type] . "<br>名字：" . $model->account->gcash_name . "<br>电话：" . $model->account->gcash_phone;
                                    } elseif ($model->type == WithdrawBill::MAYA_ACCOUNT) {
                                        return $data[$model->type] . "<br>名字：" . $model->account->maya_name . "<br>电话：" . $model->account->maya_phone;
                                    } elseif ($model->type == WithdrawBill::BANK_CARD) {
                                        return $data[$model->type] . "<br>名字：" . $model->card->username . "<br>卡号：" . $model->card->bank_card . "<br>开户行：" . $model->card->bank_address;
                                    } else {
                                        return $data[$model->type];
                                    }
                                },
                                'headerOptions' => ['width' => '250px'],
                            ],
                            [
                                'headerOptions' => ['width' => '114px'],
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
                                'class' => '\kartik\grid\EditableColumn',
                                'attribute' => 'remark',
                                'format' => 'raw',
                                'headerOptions' => ['width' => '120px'],
                                'editableOptions' => [
                                    'asPopover' => true,
                                    'inputType' => \kartik\editable\Editable::INPUT_TEXTAREA,//只需添加如下代码
                                    'options' => [
                                        'rows' => 4,
                                    ],
                                ],
                                'value' => function ($model) {
                                    if (!empty($model->remark)) {
                                        if (mb_strlen($model->remark) > 4) {
                                            $remark = mb_substr($model->remark, 0, 4, 'utf-8') . "..";
                                        } else {
                                            $remark = $model->remark;
                                        }
                                    } else {
                                        $remark = "(暂无)";
                                    }
                                    return $remark;
                                },
                            ],
                            [
                                'class' => '\kartik\grid\EditableColumn',
                                'attribute' => 'user_remark',
                                'format' => 'raw',
                                'headerOptions' => ['width' => '120px'],
                                'editableOptions' => [
                                    'asPopover' => true,
                                    'inputType' => \kartik\editable\Editable::INPUT_TEXTAREA,//只需添加如下代码
                                    'options' => [
                                        'rows' => 4,
                                    ],
                                ],
                                'value' => function ($model) {
                                    if (!empty($model->user_remark)) {
                                        if (mb_strlen($model->user_remark) > 4) {
                                            $user_remark = mb_substr($model->user_remark, 0, 4, 'utf-8') . "..";
                                        } else {
                                            $user_remark = $model->user_remark;
                                        }
                                    } else {
                                        $user_remark = "(暂无)";
                                    }
                                    return $user_remark;
                                },
                            ],

                            [
                                'headerOptions' => ['width' => '102px'],
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
                                'headerOptions' => ['width' => '60px'],
                                'class' => 'yii\grid\ActionColumn',
                                'header' => '操作',
                                'template' => '{pass} {refuse} {pay-on-behalf}',
                                'buttons' => [
                                    'pass' => function ($url, $model, $key) {
                                        if ($model->status == 0) {
                                            return Html::linkButton(['check', 'id' => $model->id], '通过', [
                                                'class' => 'btn btn-success btn-sm',
                                                'style' => 'margin-bottom: 10px',
                                            ]);
                                        }
                                    },
                                    'refuse' => function ($url, $model, $key) {
                                        if ($model->status == 0) {
                                            return Html::linkButton(['no-pass', 'id' => $model->id], '拒绝', [
                                                'class' => 'btn btn-primary btn-sm',
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'style' => 'margin-bottom: 10px',
                                            ]);
                                        }
                                    },
                                    'pay-on-behalf' => function ($url, $model, $key) {
                                        if (Yii::$app->params['thisAppEnglishName'] == "task_cn") {
                                            if ($model->status == 0) {
                                                if ($model->type == 3 || $model->type == 5) {
                                                    return Html::linkButton(['pay-on-behalf', 'id' => $model->id], '代付', [
                                                        'class' => 'btn btn-warning btn-sm',
                                                        'data-toggle' => 'modal',
                                                        'data-target' => '#ajaxModal',
                                                        'style' => 'margin-bottom: 10px'
                                                    ]);
                                                }
                                            }
                                        }
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        return Html::delete(['delete', 'id' => $model->id]);
                                    },
                                ],
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
                            '<div class="pull-left btn-toolbar">'
//                            . '<div style="float:left;">批量备注：<input type="text" id="remark" style="height: 34px;width: 60%" placeholder="(请填写驳回理由)"></div>'
//                            . Html::a('批量通过', Url::to(['batch-edit']), ['class' => 'btn btn-success', 'onclick' => 'ycmcBatchVerify(this, 1, document.getElementById("remark").value);return false;'])
//                            . Html::a('批量拒绝', Url::to(['batch-edit']), ['class' => 'btn btn-primary', 'onclick' => 'ycmcBatchVerify(this, 2, document.getElementById("remark").value);return false;'])
//                            . Html::a('批量取消', Url::to(['batch-edit']), ['class' => 'btn btn-warning', 'onclick' => 'ycmcBatchVerify(this, 3, document.getElementById("remark").value);return false;'])
//                        . Html::a('批量删除', Url::to(['delete-all']), ['class' => 'btn btn-danger', 'onclick' => 'ycmcBatchVerify(this);return false;'])
//                            . Html::a('支付导出', Url::to(['pay-export']), ['class' => 'btn btn-dropbox pay-export'])
                            . Html::linkButton(['export'], '日期导出', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal', 'class' => 'btn btn-primary'])
                            . Html::a('提示音开关', Url::to(['withdraw-switch']), ['class' => 'btn btn-info'])
                            . '</div>',
                            '{toggleData}',
                            '{export}'
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
<?php
$this->registerJs('
$(".pay-export").on("click", function (e) {
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