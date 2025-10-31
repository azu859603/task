<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '任务订单';
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
                        //[
                        //'class' => '\kartik\grid\CheckboxColumn',
                        //'rowSelectedClass' => GridView::TYPE_INFO,
                        //'visible' => true,
                        //],
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],

                        'id',
                        [
                            'attribute' => 'cid',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'cid', $laber_category, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) use ($laber_category) {
                                return $laber_category[$model->cid];
                            },
                        ],
                        [
                            'attribute' => 'project.cid',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'project.cid', $category, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) use ($category) {
                                return $category[$model->project->cid];
                            },
                        ],
                        [
                            'headerOptions' => ['width' => '216px'],
                            'label' => '关联用户',
                            'attribute' => 'member.mobile',
                            'filter' => Html::activeTextInput($searchModel, 'member.mobile', [
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
                                $nickname = !empty($model->member->nickname) ? $model->member->nickname : "(暂无)";
                                return Html::a(
                                    "账号：" . $model->member->mobile . '<br>' .
                                    "昵称：" . Html::encode($nickname) . '<br>' .
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
                            'headerOptions' => ['width' => '200px'],
                            'attribute' => 'project.translation.title',
                            'filter' => false,
                            'format' => 'raw',
                            'value' => function ($model) {
                                return Html::a(
                                    "社媒平台用户名：" . $model->username . "</br>" .
                                    "任务ID：" . $model->project->id . "</br>" .
                                    "任务标题:" . $model->project->translation->title,
                                    ['/task/order/view', 'id' => $model->project->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            }
                        ],
                        [
                            'attribute' => 'video_url',
                            'format' => 'raw',
                            'value' => function ($model) {
                                if (!empty($model->video_url)) {
                                    return "<a href='$model->video_url' target='_blank'>点击查看";
                                } else {
                                    return "";
                                }
                            }
                        ],
                        [
                            'headerOptions' => ['width' => '168px'],
                            'attribute' => 'images_list',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return \common\helpers\ImageHelper::fancyBoxs($model->images_list);
                            },
                            'format' => 'raw'
                        ],
                        'money',
                        'code',
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
                            'attribute' => 'status',
                            'value' => function ($model, $key, $index, $column) {
                                return \common\models\task\Order::$statusExplain[$model->status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'status', \common\models\task\Order::$statusExplain, [
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
                            'label' => '审核人',
                            'attribute' => 'manager.username',
                            'filter' => Html::activeTextInput($searchModel, 'manager.username', [
                                    'class' => 'form-control',
                                    'placeholder' => '输入账号查询'
                                ]
                            ),
                            'value' => function ($model) {
                                return $model->manager->username;
                            },
                            'format' => 'raw',
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{pass} {no-pass}',
                            'buttons' => [
                                'pass' => function ($url, $model, $key) {
                                    if ($model->status == 1) {
                                        return Html::linkButton(['check', 'id' => $model->id, 'status' => 2], '通过', [
                                            'class' => 'btn btn-success btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '是否立即执行通过操作？', '请谨慎操作');return false;",
                                            'style' => 'margin-bottom: 10px',
                                        ]);
                                    }
                                },
                                'no-pass' => function ($url, $model, $key) {
                                    if ($model->status == 1) {
                                        return Html::linkButton(['no-pass', 'id' => $model->id, 'status' => 3], '驳回', [
                                            'class' => 'btn btn-warning btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'style' => 'margin-bottom: 10px',
                                        ]);
                                    }
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::delete(['delete', 'id' => $model->id], '删除', ['style' => 'margin-bottom: 10px']);
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
