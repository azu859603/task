<?php

use common\helpers\Html;
use common\helpers\Url;
use kartik\grid\GridView;
/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '短剧列表';
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
                        [
                        'class' => '\kartik\grid\CheckboxColumn',
                        'rowSelectedClass' => GridView::TYPE_INFO,
                        'visible' => true,
                        ],
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false,
                        ],

                        'id',
                        [
                            'attribute' => 'sort',
                            'format' => 'raw',
                            'headerOptions' => ['class' => 'col-md-1'],
                            'value' => function ($model, $key, $index, $column) {
                                return Html::sort($model->sort);
                            }
                        ],
                        [
                            'attribute' => 'translation.banner',
                            'filter' => false, //不显示搜索框
                            'value' => function ($model) {
                                return \common\helpers\ImageHelper::fancyBox(Yii::$app->debris->backendConfig('short_plays_img_url').$model->translation->banner);
                            },
                            'format' => 'raw'
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
                            'attribute' => 'amount',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'number',
                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'buy_number',
                        ],
                        //'label',
                        [
                            'class' => '\kartik\grid\EditableColumn',
                            'editableOptions' => [
                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
                                'data' => [1 => '是', 0 => '否'],
                                'formOptions' => [
                                    'action' => ['index']
                                ]
                            ],
                            'attribute' => 'is_top',
                            'value' => function ($model, $key, $index, $column) {
                                return [1 => '是', 0 => '否'][$model->is_top];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'is_top', [1 => '是', 0 => '否'], [
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
                            'label' => '发布人',
                            'value' => function ($model) {
                                return $model->manager->username;
                            },
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
                            'template' => '{edit} {detail}',
                            'buttons' => [
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['edit', 'id' => $model->id]);
                                },
                                'detail' => function ($url, $model, $key) {
                                    return Html::edit(['/dj/short-plays-detail/index', 'pid' => $model->id], '剧集', ['class' => 'btn btn-success btn-sm']);
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
//                        . Html::a('批量下单', Url::to(['add-order-one']), ['class' => 'btn btn-success', 'onclick' => 'addOrder(this);return false;'])
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


<script>
    function addOrder(obj, ...params) {
        var ids = $("#grid").yiiGridView("getSelectedRows");
        console.log(ids);
        if (ids.length === 0) {
            rfError("错误", "没有选中任何项");return;
        }
        var ids_str = ids.join()
        console.log(ids_str)
        // var mobile = document.getElementById("searchmodel-member-mobile").value;
        // if (!mobile) {
        //     rfError("错误", "请输入卖家进行操作");return;
        // }
        var return_url = $(obj).attr("href");
        window.location.href = return_url+"?ids="+ids_str;
    }
</script>
