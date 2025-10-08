<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\dj\Orders;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '订单列表';
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
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],

                        'id',
                        [
                            'label' => '买家信息',
                            'attribute' => 'member_id',
                            'filter' => Html::activeTextInput($searchModel, 'member_id', [
                                    'class' => 'form-control',
                                    'placeholder' => '买家账号'
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
                            'label' => '卖家信息',
                            'attribute' => 'seller.mobile',
                            'filter' => Html::activeTextInput($searchModel, 'seller.mobile', [
                                    'class' => 'form-control',
                                    'placeholder' => '卖家账号'
                                ]
                            ),
                            'value' => function ($model) {
                                if (!empty($model->seller->remark)) {
                                    if (mb_strlen($model->seller->remark) > 10) {
                                        $remark = mb_substr($model->seller->remark, 0, 10, 'utf-8') . "..";
                                    } else {
                                        $remark = $model->seller->remark;
                                    }
                                } else {
                                    $remark = "(暂无)";
                                }
                                $realname = !empty($model->seller->realname) ? $model->seller->realname : "(暂无)";
                                return Html::a(
                                    "账号：" . $model->seller->mobile . '<br>' .
                                    "昵称：" . $model->seller->nickname . '<br>' .
                                    "姓名：" . $realname . '<br>' .
                                    "备注：" . $remark . '<br>',
                                    ['/seller/seller/view', 'id' => $model->seller->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],
                        [
                            'attribute' => 'shortPlaysList.translation.title',
                            'filter' => false,
                            'value' => function ($model) {
                                return $model->shortPlaysList->translation->title;
                            }
                        ],
                        'money',
                        'dx_money',
                        'income',
                        'private_key',
                        [
                            'attribute' => 'key_status',
                            'value' => function ($model, $key, $index, $column) {
                                return \common\models\dj\Orders::$keyStatusExplain[$model->key_status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'key_status', \common\models\dj\Orders::$keyStatusExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
//                        [
//                            'attribute' => 'status',
//                            'value' => function ($model, $key, $index, $column) {
//                                return \common\models\dj\Orders::$statusExplain[$model->status];
//                            },
//                            'filter' => Html::activeDropDownList($searchModel, 'status', \common\models\dj\Orders::$statusExplain, [
//                                'prompt' => '全部',
//                                'class' => 'form-control'
//                            ])
//                        ],
                        [
                            'attribute' => 'income_status',
                            'value' => function ($model, $key, $index, $column) {
                                return '<span class="label label-' . Orders::$statusColorExplain[$model->income_status] . '">' . Orders::$incomeStatusExplain[$model->income_status] . '</span>';

                            },
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'income_status', \common\models\dj\Orders::$incomeStatusExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
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
//                        [
//                            'headerOptions' => ['width' => '102px'],
//                            'attribute' => 'updated_at',
//                            'filter' => \kartik\daterange\DateRangePicker::widget([
//                                'model' => $searchModel,
//                                'convertFormat' => true,
//                                'name' => 'updated_at',
//                                'attribute' => 'updated_at',
//                                'hideInput' => true,
//                                'options' => ['placeholder' => '请选择时间段...', 'class' => 'form-control'],
//                                'pluginOptions' => [
//                                    'timePicker' => true,
//                                    'locale' => [
//                                        'format' => 'Y-m-d',
//                                        'separator' => '~'
//                                    ],
//                                    'opens' => 'left'
//                                ],
//                                'pluginEvents' => [
//                                    "cancel.daterangepicker" => "function(ev, picker) {
//                            $(picker.element[0].children[1]).val('');
//                            $(picker.element[0].children[0].children[1]).val('').trigger('change');
//                        }"
//                                ]
//                            ]),
//                            'value' => function ($model) {
//                                return \common\helpers\DateHelper::dateTime($model->updated_at);
//                            },
//                        ],
                        [
                            'headerOptions' => ['width' => '102px'],
                            'attribute' => 'over_time',
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'convertFormat' => true,
                                'name' => 'over_time',
                                'attribute' => 'over_time',
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
                                return \common\helpers\DateHelper::dateTime($model->over_time);
                            },
                        ],
//                        [
//                            'class' => 'yii\grid\ActionColumn',
//                            'header' => '操作',
//                            'template' => '{return-goods}',
//                            'buttons' => [
//                                'return-goods' => function ($url, $model, $key) {
//                                    $return_goods_time = Yii::$app->debris->backendConfig('return_goods_time');
////                                    var_dump(time()-86400-7200);exit;
//                                    if ($model->status == 0 && $model->created_at < time() - ($return_goods_time * 86400)) {
//                                        return Html::linkButton(['return-goods', 'id' => $model->id], '退货', [
//                                            'class' => 'btn btn-danger btn-sm',
//                                            'onclick' => "rfTwiceAffirm(this, '确定执行将返回买家购买短剧金额,是否执行？', '请谨慎操作');return false;",
//                                            'style' => 'margin-bottom: 10px',
//                                        ]);
//                                    }
//                                },
//                                'delete' => function ($url, $model, $key) {
//                                    return Html::delete(['delete', 'id' => $model->id]);
//                                },
//                            ],
//                        ],
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
                        . Html::a('一键完成', Url::to(['over-order']), ['class' => 'btn btn-success', 'onclick' => 'overOrder(this);return false;'])
//                        . Html::create(['ajax-edit'], '创建', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal', 'class' => 'btn btn-primary'])
                        //. Html::a('批量删除', Url::to(['delete-all']), ['class' => 'btn btn-danger', 'onclick' => 'ycmcBatchVerify(this);return false;'])
                        . '</div>',
                        '{toggleData}',
                        '{export}'
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>


<script>

    function overOrder(obj, ...params) {
        var mobile = document.getElementById("searchmodel-seller-mobile").value;
        if (!mobile) {
            rfError("错误", "请输入卖家进行操作");
            return;
        }
        var return_url = $(obj).attr("href");
        window.location.href = return_url + "?mobile=" + mobile;
    }


</script>
