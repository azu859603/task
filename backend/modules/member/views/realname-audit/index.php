<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\helpers\ImageHelper;
use common\models\member\RealnameAudit;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '实名认证审核';
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
                        'realname',
                        'identification_number',
                        [
                            'attribute' => 'front',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return ImageHelper::fancyBox($model->front);
                            },
                            'format' => 'raw'
                        ],
                        [
                            'attribute' => 'reverse',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return ImageHelper::fancyBox($model->reverse);
                            },
                            'format' => 'raw'
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
                            'attribute' => 'type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'type', RealnameAudit::$typeExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return RealnameAudit::$typeExplain[$model->type];
                            },
                            'headerOptions' => ['width' => '114px'],
                        ],
                        [
                            'headerOptions' => ['width' => '114px'],
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status', RealnameAudit::$statusExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return RealnameAudit::$statusExplain[$model->status];
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
                            'label' => '审核人',
                            'value' => function ($model) {
                                return !empty($model->manager->username) ? $model->manager->username : "暂无";
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{pass} {reject} {ajax-edit} {delete}',
                            'buttons' => [
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::linkButton(['ajax-edit', 'id' => $model->id], '编辑', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                        'class' => 'btn btn-primary btn-sm',
                                    ]);
                                },
                                'pass' => function ($url, $model, $key) {
                                    if ($model->status == 0) {
                                        return Html::linkButton(['pass', 'id' => $model->id], '通过', [
                                            'class' => 'btn btn-success btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '通过实名认证申请,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }
                                },
                                'reject' => function ($url, $model, $key) {
                                    if ($model->status == 0) {
                                        return Html::linkButton(['reject', 'id' => $model->id], '拒绝', [
                                            'class' => 'btn btn-warning btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
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
//                        . Html::create(['ajax-edit'], '创建', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal', 'class' => 'btn btn-primary'])
                        //. Html::a('批量删除', Url::to(['delete-all']), ['class' => 'btn btn-danger', 'onclick' => 'ycmcBatchVerify(this);return false;'])
//                        . Html::a('批量通过', Url::to(['batch-edit']), ['class' => 'btn btn-success', 'onclick' => 'ycmcBatchVerify(this, 1);return false;'])
//                        . Html::a('批量拒绝', Url::to(['batch-edit']), ['class' => 'btn btn-warning', 'onclick' => 'ycmcBatchVerify(this, 2);return false;'])
//                        . Html::a('提示音开关', Url::to(['realname-switch']), ['class' => 'btn btn-info'])
                        . '</div>',
                        '{toggleData}',
                        '{export}'
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
