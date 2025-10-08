<?php

use common\helpers\BcHelper;
use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\tea\InvestmentProject;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '产品列表';
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
                            'attribute' => 'sort',
                            'format' => 'raw',
                            'headerOptions' => ['width' => '80px'],
                            'value' => function ($model, $key, $index, $column) {
                                return Html::sort($model->sort);
                            }
                        ],
//                        'id',


//                        [
//                            'headerOptions' => ['width' => '114px'],
//                            'label' => '分类',
//                            'attribute' => 'type',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', $category_list, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'format' => 'raw',
//                            'value' => function ($model) use ($category_list) {
//                                return $category_list[$model->type];
//                            },
//                        ],


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
                            'headerOptions' => ['width' => '114px'],
                        ],
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'attribute' => 'title',
                            'format' => 'raw',
                            'editableOptions' => [
                                'asPopover' => false,
                                'inputType' => \kartik\editable\Editable::INPUT_TEXTAREA,//只需添加如下代码
                                'options' => [
                                    'rows' => 4,
                                ],
                            ],
//                            'value' => function ($model) {
//                                if (!empty($model->title) && mb_strlen($model->title) > 8) {
//                                    return mb_substr($model->title, 0, 8, 'utf-8') . "..";
//                                } else {
//                                    return $model->title;
//                                }
//                            },
                        ],
                        //'project_img',
                        [
                            'attribute' => 'all_investment_amount',
                            'format' => 'raw',
                            'headerOptions' => ['width' => '90px'],
                            'value' => function ($model) {
                                return BcHelper::div($model->all_investment_amount, 10000, 0) . "万元";
                            }
                        ],
                        ['attribute' => 'deadline', 'format' => 'raw', 'headerOptions' => ['width' => '90px'],],


                        //'all_investment_amount',
                        //'can_investment_amount',
                        ['class' => '\kartik\grid\EditableColumn', 'attribute' => 'schedule', 'format' => 'raw', 'headerOptions' => ['width' => '90px'],],
                        ['class' => '\kartik\grid\EditableColumn', 'attribute' => 'least_amount', 'format' => 'raw', 'headerOptions' => ['width' => '90px'],],
                        //'most_amount',
                        //'limit_times:datetime',
                        //'investment_number',
                        //'deadline',
                        ['class' => '\kartik\grid\EditableColumn', 'attribute' => 'income', 'format' => 'raw', 'headerOptions' => ['width' => '90px'],],
                        ['class' => '\kartik\grid\EditableColumn', 'attribute' => 'remark', 'format' => 'raw'],

                        [
                            'headerOptions' => ['width' => '50px'],
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => ['1' => '展示', '0' => '隐藏'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'attribute' => 'home_show_switch',
                            'value' => function ($model, $key, $index, $column) {
                                return ['1' => '展示', '0' => '隐藏'][$model->home_show_switch];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'home_show_switch', ['1' => '展示', '0' => '隐藏'], [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],

                        [
                            'label' => '自动增长',
                            'attribute' => 'increase_status',
                            'headerOptions' => ['width' => '50px'],
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => ['1' => '开启', '0' => '关闭'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'filter' => Html::activeDropDownList($searchModel, 'increase_status', ['1' => '开启', '0' => '关闭'], [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ]),
                            'value' => function ($model, $key, $index, $column) {
                                return ['1' => '开启', '0' => '关闭'][$model->increase_status];
                            },
                        ],
                        [
                            'headerOptions' => ['width' => '50px'],
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => ['1' => '启用', '0' => '禁用'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'attribute' => 'project_status',
                            'value' => function ($model, $key, $index, $column) {
                                return ['1' => '启用', '0' => '禁用'][$model->project_status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'project_status', ['1' => '启用', '0' => '禁用'], [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'headerOptions' => ['width' => '50px'],
//                            'class' => '\kartik\grid\EditableColumn',
//                            'editableOptions' => [
//                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
//                                'data' => ['1' => '购买中', '0' => '购买已满'],
//                                'formOptions' => [
//                                    'action' => ['index']
//                                ]
//                            ],
                            'attribute' => 'status',
                            'value' => function ($model, $key, $index, $column) {
                                return ['1' => '购买中', '0' => '购买已满'][$model->status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['1' => '购买中', '0' => '购买已满'], [
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
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{edit}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id], '编辑');
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
                        . Html::create(['edit'], '创建', ['class' => 'btn btn-primary'])
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
