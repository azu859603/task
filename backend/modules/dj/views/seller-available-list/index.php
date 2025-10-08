<?php

use common\helpers\BcHelper;
use common\helpers\Html;
use common\helpers\Url;
use common\models\member\Member;
use kartik\grid\GridView;
use yii\widgets\ActiveForm;
use kartik\daterange\DateRangePicker;
use common\models\dj\SellerAvailableList;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '卖家上架短剧列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-xs-12">
        <div class="tab-content">
            <div class="active tab-pane">
                <div class="row">
                    <div class="col-sm-3">
                        <?php $form = ActiveForm::begin([
                            'action' => Url::to(['index']),
                            'method' => 'get'
                        ]); ?>
                        <div class="input-group m-b">
                            <?= Html::textInput('keyword', $keyword, [
                                'placeholder' => '请输入短剧名称',
                                'class' => 'form-control'
                            ]) ?>
                            <?= Html::tag('span', '<button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>', ['class' => 'input-group-btn']) ?>
                        </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>
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
                            'label' => '卖家信息',
                            'attribute' => 'member.mobile',
                            'filter' => Html::activeTextInput($searchModel, 'member.mobile', [
                                    'class' => 'form-control',
                                    'placeholder' => '会员账号'
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
                                return Html::a(
                                    "账号：" . $model->member->mobile . '<br>' .
                                    "昵称：" . $model->member->nickname . '<br>' .
                                    "姓名：" . $realname . '<br>' .
                                    "备注：" . $remark . '<br>',
                                    ['/member/member/view', 'id' => $model->member->id],
                                    [
                                        'data-toggle' => 'modal',
                                        'data-target' => '#ajaxModal',
                                    ]);
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '209px'],
                        ],

                        [
                            'attribute' => 'shortPlaysList.translation.title',
                            'filter' => false,
                            'value' => function ($model) {
                                return $model->shortPlaysList->translation->title;
                            }
                        ],
                        [
                            'attribute' => 'shortPlaysList.amount',
                            'filter' => false,
                            'value' => function ($model) {
                                return $model->shortPlaysList->amount;
                            }
                        ],
                        [
                            'label' => "代销价/利润",
                            'value' => function ($model) {
                                $memberInfo = Member::find()->where(['id' => $model->member_id])->with(['sellerLevel'])->one();
                                $income = BcHelper::mul(BcHelper::div($memberInfo->sellerLevel->profit, 100, 4), $model->shortPlaysList->amount);
                                $dx_money = BcHelper::sub($model->shortPlaysList->amount, $income);
                                return $dx_money . "/" . $income;
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function ($model, $key, $index, $column) {
//                                return \common\models\dj\SellerAvailableList::$statusExplain[$model->status];
                                return '<span class="label label-' . SellerAvailableList::$statusColorExplain[$model->status] . '">' . SellerAvailableList::$statusExplain[$model->status] . '</span>';

                            },
                            'filter' => Html::activeDropDownList($searchModel, 'status', \common\models\dj\SellerAvailableList::$statusExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'label' => '预售数量',
                            'value' => function ($model) {
                                return \common\models\dj\SellerAvailableOrder::find()->where(['pid' => $model->pid, 'member_id' => $model['member_id']])->sum('buy_number') >> 0;
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
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
//                            'template' => '{available} {removed} {add}',
                            'template' => '{add}',
                            'buttons' => [
                                'removed' => function ($url, $model, $key) {
                                    if ($model->status == 1) {
                                        return Html::linkButton(['check', 'id' => $model->id, 'status' => 0], '下架', [
                                            'class' => 'btn btn-danger btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '通过后该用户将下架该短剧,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }
                                },
                                'available' => function ($url, $model, $key) {
                                    if ($model->status == 0) {
                                        return Html::linkButton(['check', 'id' => $model->id, 'status' => 1], '上架', [
                                            'class' => 'btn btn-success btn-sm',
                                            'onclick' => "rfTwiceAffirm(this, '通过后该用户将上架该短剧,是否执行？', '请谨慎操作');return false;",
                                        ]);
                                    }
                                },
                                'add' => function ($url, $model, $key) {
                                    return Html::linkButton(['add', 'id' => $model->id, 'seller_id' => $model->member_id], '下单', ['class' => 'btn btn-primary btn-sm',]);
                                },
                                'edit' => function ($url, $model, $key) {
                                    return Html::edit(['ajax-edit', 'id' => $model->id], '编辑', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal']);
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
//                        . Html::create(['ajax-edit'], '创建', ['data-toggle' => 'modal', 'data-target' => '#ajaxModal', 'class' => 'btn btn-primary'])
                        . Html::a('批量下单', Url::to(['add-order']), ['class' => 'btn btn-success', 'onclick' => 'addOrder(this);return false;'])
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
            rfError("错误", "没有选中任何项");
            return;
        }
        var ids_str = ids.join()
        console.log(ids_str)
        var mobile = document.getElementById("searchmodel-member-mobile").value;
        if (!mobile) {
            rfError("错误", "请输入卖家进行操作");
            return;
        }
        var return_url = $(obj).attr("href");
        window.location.href = return_url + "?ids=" + ids_str + "&mobile=" + mobile;
    }


</script>