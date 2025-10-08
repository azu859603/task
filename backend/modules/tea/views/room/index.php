<?php

use common\helpers\DateHelper;
use common\helpers\Html;
use common\helpers\ImageHelper;
use kartik\grid\GridView;
use common\models\tea\Room;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '群组管理';
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
                    'export' => false,
                    'columns' => [
                        [
                            'attribute' => 'avatar',
                            'value' => function ($model) {
                                return Html::img(ImageHelper::defaultHeaderPortrait(Html::encode($model->avatar)),
                                    [
                                        'class' => 'img-circle rf-img-md img-bordered-sm',
                                    ]);
                            },
                            'filter' => false,
                            'format' => 'raw',
                        ],
                        'name',
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_TEXT,
                                'formOptions' => [
                                    'action' => ['table-edit']
                                ]
                            ],
                            'attribute' => 'remark',
                        ],

                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => Room::$statusExplain,
                                'formOptions' => [
                                    'action' => ['change-status']
                                ]
                            ],
                            'attribute' => 'status',
                            'value' => function ($model){
                                return Room::$statusExplain[$model->status];
                            },
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'status',  Room::$statusExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
//                        [
//                            'label' => '会员数',
//                            'filter' => false,
//                            'value' => function ($model) {
//                                return $model->getMember()->count();
//                            },
//                        ],
//                        [
//                            'label' => '在线数',
//                            'filter' => false,
//                            'value' => function ($model) {
//                                return $model->getOnlineMember()->count();
//                            },
//                        ],
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
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{add} {sub} {viw} {edit} {destroy}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id]);
                                },
                                'destroy' => function ($url, $model, $key) {
                                    return Html::delete(['destroy', 'id' => $model->id]);
                                },
                                'add' => function ($url, $model, $key) {
                                    return Html::a(
                                        "加人",
                                        ['add', 'id' => $model->id],
                                        [
                                            'class' => 'btn btn-primary btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                        ]);
                                },
                                'sub' => function ($url, $model, $key) {
                                    return Html::a(
                                        "移除",
                                        ['sub', 'id' => $model->id],
                                        [
                                            'class' => 'btn btn-danger btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                        ]);
                                },
                                'viw' => function ($url, $model, $key) {
                                    return Html::a(
                                        "成员",
                                        ['view', 'id' => $model->id],
                                        [
                                            'class' => 'btn btn-success btn-sm',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                        ]);
                                },
                            ],
                            'headerOptions' => ['width' => '125px']
                        ]
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>
