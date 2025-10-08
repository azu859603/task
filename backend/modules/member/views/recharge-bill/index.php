<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\member\RechargeBill;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '充值审核';
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
                        [
                            'class' => '\kartik\grid\CheckboxColumn',
                            'rowSelectedClass' => GridView::TYPE_INFO,
                            'visible' => true,
                        ],

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
                                    "姓名：" . Html::encode($realname) . '<br>' .
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
                            'attribute' => 'member.type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'member.type', \common\models\member\Member::$typeExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return \common\models\member\Member::$typeExplain[$model->member->type];
                            }
                        ],
                        [
                            'label' => '充值金额(' . $sum_recharge_money . ')',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'attribute' => 'recharge_money',
                        ],
                        [
                            'headerOptions' => ['class' => 'col-md-1'],
                            'attribute' => 'real_recharge_money',
                            'value' => function ($model) {
                                if ($model->type == 10000) {
                                    return $model->real_recharge_money . "USDT";
                                } else {
                                    return $model->real_recharge_money;
                                }
                            }
                        ],
//                        [
//                            'headerOptions' => ['class' => 'col-md-1'],
//                            'attribute' => 'username'
//                        ],
                        [
                            'attribute' => 'type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'type', $category, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) use ($category) {
                                if ($model->type == 10000 || $model->type == 10001 || $model->type == 10002) {
                                    return $category[$model->type];
                                } else {
                                    return $category[$model->type] . "(" . $model->pay_code . ")";
                                }

                            },
                            'headerOptions' => ['width' => '114px'],
                        ],
                        [
                            'attribute' => 'images',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return \common\helpers\ImageHelper::fancyBox($model->images);
                            },
                            'format' => 'raw'
                        ],
                        [
                            'headerOptions' => ['width' => '114px'],
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status', RechargeBill::$statusExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return '<span class="label label-' . RechargeBill::$statusColorExplain[$model->status] . '">' . RechargeBill::$statusExplain[$model->status] . '</span>';
                            },
                        ],
//                        [
//                            'headerOptions' => ['width' => '120px'],
//                            'attribute' => 'user_remark'
//                        ],
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
                            'template' => '{warning_switch} {pass} {refuse}',
                            'buttons' => [
                                'warning_switch' => function ($url, $model, $key) {
                                    if ($model->status == 0 && $model->warning_switch == 1) {
                                        return Html::linkButton(['warning-switch', 'id' => $model->id], '忽略', [
                                            'class' => 'btn btn-default btn-sm',
                                            'style' => 'margin-bottom: 10px',
                                        ]);
                                    }
                                },
                                'pass' => function ($url, $model, $key) {
                                    if ($model->status == 0 || $model->status == 3) {
                                        return Html::linkButton(['check', 'id' => $model->id, 'status' => 1], '通过', [
                                            'class' => 'btn btn-success btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '通过后充值金额将添加给用户,是否执行？', '请谨慎操作');return false;",
                                            'style' => 'margin-bottom: 10px',
                                        ]);
                                    }
                                },
                                'refuse' => function ($url, $model, $key) {
                                    if ($model->status == 0) {
                                        return Html::linkButton(['no-pass', 'id' => $model->id, 'status' => 2], '拒绝', [
                                            'class' => 'btn btn-warning btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'style' => 'margin-bottom: 10px',
                                        ]);
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
                        . '<div style="float:left;">批量备注：<input type="text" id="remark" style="height: 34px;width: 60%" placeholder="(请填写驳回理由)"></div>'
                        . Html::a('批量通过', Url::to(['batch-edit']), ['class' => 'btn btn-success', 'onclick' => 'ycmcBatchVerify(this, 1, document.getElementById("remark").value);return false;'])
                        . Html::a('批量拒绝', Url::to(['batch-edit']), ['class' => 'btn btn-warning', 'onclick' => 'ycmcBatchVerify(this, 2, document.getElementById("remark").value);return false;'])
                        . Html::a('提示音开关', Url::to(['recharge-switch']), ['class' => 'btn btn-info'])
//                        . Html::a('批量删除', Url::to(['delete-all']), ['class' => 'btn btn-danger', 'onclick' => 'ycmcBatchVerify(this);return false;'])
                        . '</div>',
                        '{toggleData}',
                        '{export}'
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>

<!--<script>-->
<!--    setInterval(function () {-->
<!--        window.location.reload();-->
<!--    }, 10000);-->
<!--</script>-->
