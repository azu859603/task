<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use yii\helpers\Html as BaseHtml;
use common\helpers\ImageHelper;
use common\helpers\DateHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '图片列表';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::linkButton(['edit'], '<i class="icon ion-plus"></i>创建', [
                        'class' => "btn btn-primary btn-xs"
                    ]) ?>
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
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'pid',
                            'filterType' => GridView::FILTER_SELECT2,
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'asPopover' => true,
                                'data' => $imgCategory,
                            ],
                            'filterWidgetOptions' => [
                                'data' => $imgCategory,
                                'options' => [
                                    'prompt' => "请选择",
                                ],
                                'hideSearch' => true,
                                'pluginOptions' => [
                                    'allowClear' => true
                                ],
                            ],
                            'filter' => $imgCategory,
                            'value' => function ($model) use ($imgCategory) {
                                return $imgCategory[$model->pid];
                            },
                        ],
                        [
                            'attribute' => 'title',
                            'class' => 'kartik\grid\EditableColumn',
                        ],

                        [
                            'attribute' => 'translation.content',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return ImageHelper::fancyBox($model->translation->content);
                            },
                            'format' => 'raw'
                        ],
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
                            'template' => '{ajax-edit} {delete}',
                            'buttons' => [
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::linkButton(['edit', 'id' => $model->id], '编辑', [
                                        'class' => 'btn btn-primary btn-sm',
                                    ]);
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
