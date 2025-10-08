<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '短剧详情列表';
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
//                        [
//                            'attribute' => 'sort',
//                            'format' => 'raw',
//                            'headerOptions' => ['class' => 'col-md-1'],
//                            'value' => function ($model, $key, $index, $column) {
//                                return Html::sort($model->sort);
//                            }
//                        ],

                        [
                            'attribute' => 'shortPlaysList.translation.title',
                            'value' => function ($model) {
                                return !empty($model->shortPlaysList->translation->title) ? $model->shortPlaysList->translation->title : "暂无";
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'translation.title',
                            'value' => function ($model) {
                                return !empty($model->translation->title) ? $model->translation->title : "暂无";
                            },
                            'format' => 'raw',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'number',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'like_number',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'collect_number',
                        ],
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => ['0'=>'免费',1=>'收费'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'attribute' => 'type',
                            'value' => function ($model, $key, $index, $column) {
                                return ['0'=>'免费',1=>'收费'][$model->type];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'type', ['0'=>'免费',1=>'收费'], [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => ['1' => '启用', '0' => '禁用'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'attribute' => 'status',
                            'value' => function ($model, $key, $index, $column) {
                                return ['1' => '启用', '0' => '禁用'][$model->status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['1' => '启用', '0' => '禁用'], [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
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
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{edit} {delete}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id, 'pid' => Yii::$app->request->get('pid')]);
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
                        . Html::a('返回短剧列表', Url::to(['/dj/short-plays-list/index']), ['class' => 'btn btn-success'])
                        . Html::create(['edit', 'pid' => Yii::$app->request->get('pid')], '创建', ['class' => 'btn btn-primary'])
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
