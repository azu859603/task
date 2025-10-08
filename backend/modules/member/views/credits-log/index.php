<?php

use kartik\grid\GridView;
use common\helpers\Html;
use common\helpers\ImageHelper;
use common\models\member\CreditsLog;
use common\enums\AppEnum;

$this->title = $title;
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
                        ],
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
                            'label' => '变动数量(' . $sum_num . ')',
                            'attribute' => 'num',

                        ],
                        [
                            'label' => '变动记录',
                            'attribute' => 'new_num',
                            'filter' => Html::activeTextInput($searchModel, 'new_num', [
                                    'class' => 'form-control',
                                    'placeholder' => '最终数量'
                                ]
                            ),
                            'value' => function ($model) {
                                $operational = $model->num < 0 ? '-' : '+';
                                return $model->old_num . $operational . abs($model->num) . '=' . $model->new_num;
                            },
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
                        ],
//                        [
//                            'attribute' => 'app_id',
//                            'filter' => Html::activeDropDownList($searchModel, 'app_id', AppEnum::getMap(), [
//                                'prompt' => '全部',
//                                'class' => 'form-control'
//                            ]),
//                            'value' => function ($model) {
//                                return AppEnum::getValue($model->app_id);
//                            },
//                            'headerOptions' => ['class' => 'col-md-1'],
//                        ],
//                        [
//                            'attribute' => 'credit_group',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'credit_group', CreditsLog::$creditGroupExplain, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return CreditsLog::$creditGroupExplain[$model->credit_group];
//                            }
//                        ],
                        [
                            'attribute' => 'credit_type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'credit_type', CreditsLog::$creditTypeExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return CreditsLog::$creditTypeExplain[$model->credit_type];
                            }
                        ],
                        [
                            'attribute' => 'pay_type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'pay_type', CreditsLog::$PayTypeExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return CreditsLog::$PayTypeExplain[$model->pay_type];
                            }
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
                            'attribute' => 'map_id',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'visible' => false,
                        ],
//                        [
//                            'headerOptions' => ['width' => '60px'],
//                            'class' => 'yii\grid\ActionColumn',
//                            'header' => '操作',
//                            'template' => '{delete}',
//                            'buttons' => [
//                                'delete' => function ($url, $model, $key) {
//                                    return Html::delete(['delete', 'id' => $model->id]);
//                                },
//                            ],
//                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
