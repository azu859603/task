<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '文章分类';
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
                    'hover' => true,
                    'options' => ["class" => "grid-view", "style" => "overflow:auto", "id" => "grid"],
                    'tableOptions' => ['class' => 'table table-hover'],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],
                        // 若要全选则关闭上面打开下面的代码
                        //[
                        //'class' => '\kartik\grid\CheckboxColumn',
                        //'rowSelectedClass' => GridView::TYPE_INFO,
                        //'visible' => true,
                        //],

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
                            'filter' => false, //不显示搜索框
                            'attribute' => 'translation.title',
                            'value' => function ($model) {
                                return Html::a(!empty($model->translation->title)?$model->translation->title:"暂无", ['/common/article-details/index', 'pid' => $model->id]);
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'banner',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return \common\helpers\ImageHelper::fancyBox($model->banner);
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
                                return \common\helpers\DateHelper::dateTime($model->created_at);
                            },
                        ],
                        //'updated_at',
                        [
                            'label' => '发布人',
                            'value' => function ($model) {
                                return $model->manager->username;
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{ajax-edit}',
                            'buttons' => [
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::linkButton(['edit', 'id' => $model->id], '编辑', [
                                        'class' => 'btn btn-primary btn-sm',
                                    ]);
                                },
                            ],
                        ],
                    ],
//                    'panel' => [
//                        'heading' => false,
//                        'before' => '<div class="box-header pull-left"><i class="fa fa-fw fa-sun-o"></i><h3 class="box-title">数据管理</h3></div>',
//                        'footer' => false,
//                        'after' => '<div class="pull-left" style="margin-top: 8px">{summary}</div><div class="kv-panel-pager pull-right">{pager}</div><div class="clearfix"></div>',
//                    ],
//                    'panelFooterTemplate' => '{footer}<div class="clearfix"></div>',
//                    'toolbar' => [
//                        //'<div class="pull-left">'
//                        //. Html::a('<i class="glyphicon  glyphicon-ok-circle"></i>批量通过', Url::to('pass-all'), ['class' => 'btn btn-success', 'id' => 'bulk_forbid'])
//                        //. '</div>',
//                        '{toggleData}',
//                        '{export}'
//                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>
<!-- 若全选打开下面代码-->
<?php
//$this->registerJs('
//$("#bulk_forbid").on("click", function (e) {
//    e.preventDefault();
//    var keys = $("#grid").yiiGridView("getSelectedRows");
//    if(keys.length < 1) {
//        return rfError("", "没有选中任何项");
//    }
//    var href = $(this).attr("href");
//    window.location.href = href + "?ids=" + keys.join();
//});
//');
//?>
