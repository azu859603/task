<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use yii\helpers\Html as BaseHtml;
use common\helpers\ImageHelper;
use common\helpers\DateHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '文章列表';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::create(['edit']) ?>
                </div>
            </div>
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],
                        [
                            'attribute' => 'sort',
                            'format' => 'raw',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'value' => function ($model, $key, $index, $column) {
                                return Html::sort($model->sort);
                            }
                        ],
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'col-md-1'],
                        ],

                        [
                            'attribute' => 'pid',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'pid', $articleCategory, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) use ($articleCategory) {
                                return $articleCategory[$model->pid];
                            },
                        ],

                        [
                            'attribute' => 'translation.title',
                            'value' => function ($model) {
                                return !empty($model->translation->title)?$model->translation->title:"暂无";
                            },
                            'format' => 'raw',
                        ],
//                        [
//                            'attribute' => 'banner',
//                            'filter' => false, //不显示搜索框
//                            'value' => function ($model) {
//                                return ImageHelper::fancyBox($model->banner);
//                            },
//                            'format' => 'raw'
//                        ],
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'attribute' => 'status',
                            'filterType' => GridView::FILTER_SELECT2,
                            'filterWidgetOptions' => [
                                'data' => ['1' => '启用', '0' => '禁用'],
                                'options' => [
                                    'prompt' => '请选择',
                                ],
                                'hideSearch' => true,
                                'pluginOptions' => [
                                    'allowClear' => true
                                ],
                            ],
                            'editableOptions' => function ($model, $key, $index) {
                                return [
                                    'value' => $model->status,//原始值
                                    'displayValueConfig' => ['1' => '启用', '0' => '禁用'],//要显示的文字
                                    'header' => $model->getAttributeLabel('status'),
                                    'size' => 'md',
                                    'placement' => \kartik\popover\PopoverX::ALIGN_LEFT,//左侧弹出
                                    'inputType' => \kartik\editable\Editable::INPUT_SWITCH,
                                    'options' => [
                                        'options' => ['uncheck' => 0, 'value' => 1],//switch插件的参数
                                        'pluginOptions' => ['size' => 'small'],
                                    ],
                                ];
                            },
                            'value' => function ($model) {
                                $data = ['1' => '启用', '0' => '禁用'];
                                return $data[$model->status];
                            }
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
                                return DateHelper::dateTime($model->created_at);
                            },
                        ],
                        //'updated_at',
                        [
                            'label' => '发布人',
                            'value' => function ($model) {
                                return $model->manager->username;
                            },
                        ],
                        //'updated_by',
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{edit} {delete}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::delete(['delete', 'id' => $model->id]);
                                },
                            ]
                        ]
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>
