<?php

use common\helpers\DateHelper;
use common\helpers\Html;
use common\helpers\ImageHelper;
use kartik\grid\GridView;
use common\models\tea\ChatLog;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '聊天记录';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= Html::encode($this->title) ?></h3>
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

                        'id',
                        [
                            'attribute' => 'content',
                            'value' => function ($model) {
                                switch ($model->msg_type) {
                                    case ChatLog::TYPE_IMG:
                                        preg_match('/(?:\[)(.*)(?:\])/i', $model->content, $img);
                                        return ImageHelper::fancyBox($img[1], '100px', '100px');
                                    default:
                                        return $model->content;
                                }
                            },
                            'format' => 'raw'
                        ],
                        [
                            'attribute' => 'from_type',
                            'value' => function ($model) use ($fromTypeExplain) {
                                return $fromTypeExplain[$model->from_type];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'from_type', $fromTypeExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'label' => '发送人',
                            'attribute' => 'sender_name',
                            'filter' => Html::activeTextInput($searchModel, 'sender_name', ['class' => 'form-control']),
                            'value' => function ($model) {
                                return $model->from_type == ChatLog::ROLE_TYPE_MEMBER ? $model->senderMember->mobile : $model->senderManager->nickname;
                            },
                        ],
                        [
                            'attribute' => 'to_type',
                            'value' => function ($model) use ($toTypeExplain) {
                                return $toTypeExplain[$model->to_type];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'to_type', $toTypeExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'label' => '接收人',
                            'attribute' => 'receiver_name',
                            'filter' => Html::activeTextInput($searchModel, 'receiver_name', ['class' => 'form-control']),
                            'value' => function ($model) {
                                if ($model->to_type == ChatLog::ROLE_TYPE_MEMBER) {
                                    return $model->receiverMember->mobile;
                                }
                                if ($model->to_type == ChatLog::ROLE_TYPE_MANAGER) {
                                    return $model->receiverManager->nickname;
                                }
                                return '聊天室';
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($model) use ($statusExplain) {
                                return $statusExplain[$model->status];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'status', $statusExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'attribute' => 'is_read',
                            'value' => function ($model, $key, $index, $column) use ($isReadExplain) {
                                return $isReadExplain[$model->is_read];
                            },
                            'filter' => Html::activeDropDownList($searchModel, 'is_read', $isReadExplain, [
                                'prompt' => '全部',
                                'class' => 'form-control'
                            ])
                        ],
                        [
                            'attribute' => 'msg_type',
                            'value' => function ($model, $key, $index, $column) use ($msgTypeExplain) {
                                return $msgTypeExplain[$model->msg_type];
                            },
                            'format' => 'raw',
                            'filter' => Html::activeDropDownList($searchModel, 'msg_type', $msgTypeExplain, [
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
                                return DateHelper::dateTime($model->created_at);
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => '操作',
                            'template' => '{delete}',
                            'buttons' => [
                                'delete' => function ($url, $model, $key) {
                                    return Html::delete(['delete', 'id' => $model->id]);
                                },
                            ],
                        ],
                    ]
                ]); ?>
            </div>
        </div>
    </div>
</div>
