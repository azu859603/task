<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '图片分类';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::linkButton(['ajax-edit'], '<i class="icon ion-plus"></i>创建', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModal',
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
                            'attribute' => 'id',
                            'headerOptions' => ['class' => 'col-md-1'],
                        ],
                        [
                            'attribute' => 'title',
                            'value' => function ($model) {
                                return Html::a($model->title, ['/common/img-details/index', 'pid' => $model->id]);
                            },
                            'format' => 'raw',
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
//            'sort',
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
                                return common\helpers\DateHelper::dateTime($model->created_at);
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
                            'template' => '{ajax-edit}',
                            'buttons' => [
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::linkButton(['ajax-edit', 'id' => $model->id], '编辑', [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                        'class' => 'btn btn-primary btn-sm',
                                    ]);
                                },
                            ]
                        ]
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>
