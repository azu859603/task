<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
use common\models\common\LotterySetting;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '摇奖配置';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
                <div class="box-tools">
                    <?= Html::linkButton(['empty'], '<i class="icon ion-android-alert"></i>清零抽奖次数', [
                        'class' => "btn btn-danger btn-xs",
                        'onclick' => "rfTwiceAffirm(this, '所有用户的抽奖次数都将清零,是否执行？', '请谨慎操作');return false;",
                    ]) ?>
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

                        [
                            'attribute' => 'sort',
                            'format' => 'raw',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'value' => function ($model, $key, $index, $column) {
                                return Html::sort($model->sort);
                            }
                        ],
//                        [
//                            'attribute' => 'id',
//                            'headerOptions' => ['class' => 'col-md-1'],
//                        ],

                        [
                            'attribute' => 'lottery_name',
                            'class' => 'kartik\grid\EditableColumn',
                        ],
                        [
                            'attribute' => 'title',
                            'class' => 'kartik\grid\EditableColumn',
                        ],
//                        [
//                            'attribute' => 'banner',
//                            'filter' => false, //不显示搜索框
//                            'value' => function ($model) {
//                                return \common\helpers\ImageHelper::fancyBox($model->banner);
//                            },
//                            'format' => 'raw'
//                        ],
                        [
                            'attribute' => 'lottery_amount',
                            'class' => 'kartik\grid\EditableColumn',
                        ],
                        [
                            'attribute' => 'proportion',
                            'class' => 'kartik\grid\EditableColumn',
                        ],
                        [
                            'headerOptions' => ['width' => '114px'],
                            'attribute' => 'type',
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'type', LotterySetting::$typeExplain, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'value' => function ($model) {
                                return '<span class="label label-' . LotterySetting::$typeColorExplain[$model->type] . '">' . LotterySetting::$typeExplain[$model->type] . '</span>';
                            },
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
                                return \common\helpers\DateHelper::dateTime($model->created_at);
                            },
                        ],
                        [
                            'label' => '添加人',
                            'value' => function ($model) {
                                return $model->manager->username;
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{edit} {delete}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['ajax-edit', 'id' => $model->id], '编辑', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal']);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::delete(['delete', 'id' => $model->id]);
                                },
                            ],
                        ],
                    ],
//                    'panel' => [
//                        'heading' => false,
//                        'before' => '<div class="box-header pull-left"><i class="fa fa-fw fa-sun-o"></i><h3 class="box-title">' . $this->title . '</h3></div>',
//                        'footer' => false,
//                        'after' => '<div class="pull-left" style="margin-top: 8px">{summary}</div><div class="kv-panel-pager pull-right">{pager}</div><div class="clearfix"></div>',
//                    ],
//                    'panelFooterTemplate' => '{footer}<div class="clearfix"></div>',
//                    'toolbar' => [
//                        '<div class="pull-left btn-toolbar">'
//                        . Html::create(['ajax-edit'], '创建', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal', 'class' => 'btn btn-primary'])
//                        //. Html::a('批量删除', Url::to(['delete-all']), ['class' => 'btn btn-danger', 'onclick' => 'ycmcBatchVerify(this);return false;'])
//                        . '</div>',
//                        '{toggleData}',
//                        '{export}'
//                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
