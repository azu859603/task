<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;

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
                            'sn',
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
                            [
                                'label' => '商品标题',
                                'attribute' => 'list.title',
                                'filter' => Html::activeTextInput($searchModel, 'list.title', [
                                        'class' => 'form-control',
                                        'placeholder' => '输入商品标题查询'
                                    ]
                                ),
                                'value' => function ($model) {
                                    return $model->list ? $model->list->title : '商品不存在';
                                },
                                'format' => 'raw'
                            ],
                            [
                                'headerOptions' => ['width' => '114px'],
                                'attribute' => 'status',
                                'format' => 'raw',
                                'filter' => Html::activeDropDownList($searchModel, 'status', \common\models\tea\SignGoodsBill::$statusArray, [
                                        'prompt' => '全部',
                                        'class' => 'form-control'
                                    ]
                                ),
                                'value' => function ($model) {
                                    return '<span class="label label-' . \common\models\tea\SignGoodsBill::$statusColorExplain[$model->status] . '">' . \common\models\tea\SignGoodsBill::$statusArray[$model->status] . '</span>';
                                },
                            ],
                            [
                                'label' => '收货信息',
                                'filter' => false,
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return "收件人：" . $model->get_username . '<br>' .
                                        "联系电话：" . $model->get_mobile . '<br>' .
                                        "收货地址：" . $model->member_remark . '<br>';
                                }
                            ],
                            [
                                'class' => '\kartik\grid\EditableColumn',
                                'attribute' => 'remark',
                                'format' => 'raw',
                                'editableOptions' => [
                                    'asPopover' => true,
                                    'inputType' => \kartik\editable\Editable::INPUT_TEXTAREA,//只需添加如下代码
                                    'options' => [
                                        'rows' => 4,
                                    ],
                                ],
                                'value' => function ($model) {
                                    return $model->remark == "" ? " " : $model->remark;
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
                                'attribute' => 'ship_time',
                                'filter' => \kartik\daterange\DateRangePicker::widget([
                                    'model' => $searchModel,
                                    'convertFormat' => true,
                                    'name' => 'ship_time',
                                    'attribute' => 'ship_time',
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
                                    return \common\helpers\DateHelper::dateTime($model->ship_time);
                                },
                            ],
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
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'header' => '操作',
                                'template' => '{ship} {delete}',
                                'buttons' => [
                                    'ship' => function ($url, $model, $key) {
                                        if ($model->status == 1) {
                                            return Html::linkButton(['ship', 'id' => $model->id], '发货', [
                                                'data-toggle' => 'modal',
                                                'data-target' => '#ajaxModal',
                                                'class' => 'btn btn-success btn-sm',
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
//                        . Html::a('导出订单', Url::to(['export']), ['class' => 'btn btn-info', 'onclick' => 'ycmcGetBatchVerify(this);return false;', "target" => "_blank"])
                            . Html::a('导出订单', Url::to(['export']), ['class' => 'btn btn-info', "id" => "export"])
                            . Html::a('导入订单', Url::to(['import']), ['class' => 'btn btn-success', 'data-toggle' => 'modal', 'data-target' => '#ajaxModal',])
                            . '</div>',
                            '{toggleData}',
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
<?php
$this->registerJs('
$("#export").on("click", function (e) {
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