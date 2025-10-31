<?php


use common\helpers\Html;
use common\helpers\DateHelper;
use kartik\grid\GridView;
use common\models\member\Member;

$this->title = '推荐人列表';
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
                <div class="box-tools">
                </div>
            </div>
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    //重新定义分页样式
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false, // 不显示#
                        ],
                        [
                            'label' => '上线信息',
                            'attribute' => 'pid',
                            'filter' => Html::activeTextInput($searchModel, 'pid', [
                                    'class' => 'form-control',
                                    'placeholder' => '上线账号'
                                ]
                            ),
                            'value' => function ($model) {
                                if (!empty($model->recommendMember->remark)) {
                                    if (mb_strlen($model->recommendMember->remark) > 10) {
                                        $remark = mb_substr($model->recommendMember->remark, 0, 10, 'utf-8') . "..";
                                    } else {
                                        $remark = $model->recommendMember->remark;
                                    }
                                } else {
                                    $remark = "(暂无)";
                                }
                                $realname = !empty($model->recommendMember->realname) ? $model->recommendMember->realname : "(暂无)";
                                return Html::a(
                                    "账号：" . $model->recommendMember->mobile . '<br>' .
                                    "昵称：" . $model->recommendMember->nickname . '<br>' .
//                                    "姓名：" . $realname . '<br>' .
                                    "备注：" . $remark . '<br>',
                                    ['/member/member/view', 'id' => $model->recommendMember->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],
//                        [
//                            'label' => '上线真实姓名',
//                            'value' => function ($model) {
//                                return !empty($model->recommendMember->realname) ? $model->recommendMember->realname : "(暂无)";
//                            },
//                        ],
//                        [
//                            'label' => '上线签到状态',
//                            'value' => function ($model) {
//                                return \common\models\member\Member::$sign_array[$model->recommendMember->sign_status];
//                            },
//                        ],
                        [
                            'label' => '上线注册时间',
                            'value' => function ($model) {
                                return DateHelper::dateTime($model->recommendMember->created_at);
                            },
                        ],


                        [
                            'label' => '下线信息',
                            'attribute' => 'mobile',
                            'filter' => Html::activeTextInput($searchModel, 'mobile', [
                                    'class' => 'form-control',
                                    'placeholder' => '下线账号'
                                ]
                            ),
                            'value' => function ($model) {
                                if (!empty($model->remark)) {
                                    if (mb_strlen($model->remark) > 10) {
                                        $remark = mb_substr($model->remark, 0, 10, 'utf-8') . "..";
                                    } else {
                                        $remark = $model->remark;
                                    }
                                } else {
                                    $remark = "(暂无)";
                                }
                                $realname = !empty($model->realname) ? $model->realname : "(暂无)";
                                return Html::a(
                                    "账号：" . $model->mobile . '<br>' .
                                    "昵称：" . $model->nickname . '<br>' .
//                                    "姓名：" . $realname . '<br>' .
                                    "余额：" . $model->account->user_money . '<br>' .
                                    "备注：" . $remark . '<br>',
                                    ['/member/member/view', 'id' => $model->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],

//                        [
////                            'class' => '\kartik\grid\EditableColumn',
////                            'editableOptions' => [
////                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
////                                'data' => Member::$typeExplain,
////                                'formOptions' => [
////                                    'action' => ['index']
////                                ]
////                            ],
//                            'attribute' => 'type',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', Member::$typeExplain, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                if ($model->type == 2) {
//                                    return "后台账号：<br>" . $model->bMember->username . "<br>" . Member::$typeExplain[$model->type] . "<br>" . Member::$virtual_array[$model->is_virtual];
//                                } else {
//                                    return Member::$typeExplain[$model->type] . "<br>" . Member::$virtual_array[$model->is_virtual];
//                                }
//
//                            },
//                        ],
//                        [
//                            'attribute' => 'investment_status',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'investment_status', Member::$investment_status_array, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return '<span class="label label-' . Member::$status_color_array[$model->investment_status] . '">' . Member::$investment_status_array[$model->investment_status] . '</span>';
//                            },
//                            'headerOptions' => ['width' => '88px'],
//                        ],
                        [
                            'label' => '下线完成任务数量',
                            'attribute' => 'account.investment_number',
                            'filter' => Html::activeDropDownList($searchModel, 'account.investment_number', [1=>"完成过任务",2=>"未完成过任务"], [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return $model->account->investment_number;
                            },
                        ],
//                        [
//                            'label' => '下线签到状态',
//                            'attribute' => 'sign_status',
//                            'filter' => Html::activeDropDownList($searchModel, 'sign_status', \common\models\member\Member::$sign_array, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control',
//                                ]
//                            ),
//                            'format' => 'raw',
//                            'value' => function ($model) {
//                                return \common\models\member\Member::$sign_array[$model->sign_status];
//                            },
//                        ],
                        [
                            'label' => '下线注册时间',
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
                            'headerOptions' => ['width' => '60px'],
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{dismiss}',
                            'buttons' => [
                                'dismiss' => function ($url, $model, $key) {
                                    return Html::linkButton(['dismiss', 'id' => $model->id, 'pid' => $model->pid], '解除', [
                                        'class' => 'btn btn-danger btn-sm',
                                        'onclick' => "rfTwiceAffirm(this, '确定后将解除上下级关系,是否执行？', '请谨慎操作');return false;",
                                    ]);
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
                        '<div class="pull-left btn-toolbar">'
                        . Html::create(['ajax-edit-recommend'], '添加', ['class' => 'btn btn-primary', 'data-toggle' => 'modal', 'data-target' => '#ajaxModal',])
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

