<?php

use kartik\grid\GridView;
use common\helpers\Html;
use common\helpers\ImageHelper;
use common\models\member\Member;
use common\helpers\DateHelper;

$this->title = '会员信息';
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title"><?= $this->title; ?></h3>
                <div class="box-tools">

                    <?php
                    echo Html::create(['ajax-edit'], '创建', [
                        'data-toggle' => 'modal',
                        'data-target' => '#ajaxModal',
                    ]);
//                    echo Html::linkButton(['ajax-edit-buyer'], '<i class="icon ion-plus"></i> ' . '批量创建虚拟买家', [
//                        'class' => "btn btn-success btn-xs",
//                        'data-toggle' => 'modal',
//                        'data-target' => '#ajaxModalLg',
//                    ]);

                    ?>
                </div>
            </div>
            <div class="box-body table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    //重新定义分页样式
                    'tableOptions' => [
                        'class' => 'table table-hover rf-table',
//                        'fixedNumber' => 2,
//                        'fixedRightNumber' => 1,
                        'fixedRight' => 1,
                    ],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'visible' => false, // 不显示#
                        ],
//                        [
//                            'attribute' => 'id',
//                            'headerOptions' => ['class' => 'col-md-1'],
//                        ],
//                        [
//                            'attribute' => 'head_portrait',
//                            'value' => function ($model) {
//                                return Html::img(ImageHelper::defaultHeaderPortrait(Html::encode($model->head_portrait)),
//                                    [
//                                        'class' => 'img-circle rf-img-md img-bordered-sm',
//                                    ]);
//                            },
//                            'filter' => false,
//                            'format' => 'raw',
//                        ],
                        [
                            'attribute' => 'mobile',
                            'value' => function ($model) {
                                return Html::a(
                                        '用户ID：' . $model->id . '<br>' .
                                        $model->mobile . '<br>',
                                        ['/member/member/view', 'id' => $model->id],
                                        [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                        ]) .
//                                    Html::a('【购买记录】', "/backend/tea/investment-bill/index?SearchModel%5Bmember_id%5D=$model->mobile") . '<br>' .
                                    Html::a('【充值记录】', "/backend/member/recharge-bill/index?SearchModel%5Bmember_id%5D=$model->mobile") . '<br>' .
                                    Html::a('【提现记录】', "/backend/member/withdraw-bill/index?SearchModel%5Bmember_id%5D=$model->mobile");
                            },
                            'headerOptions' => ['width' => '123px'],
                            'format' => 'raw',
                        ],
//                        [
//                            'attribute' => 'online_status',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'online_status', Member::$online_status_array, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return '<span class="label label-' . Member::$status_color_array[$model->online_status] . '">' . Member::$online_status_array[$model->online_status] . '</span>';
//                            },
//                            'headerOptions' => ['width' => '88px'],
//                        ],
//                        [
////                            'class' => '\kartik\grid\EditableColumn',
////                            'editableOptions' => [
////                                'inputType' => \kartik\editable\Editable::INPUT_DROPDOWN_LIST,
////                                'data' => Member::$typeExplain,
////                                'formOptions' => [
////                                    'action' => ['index']
////                                ]
////                            ],
//                            'attribute' => 'type',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'type', Member::$typeExplain, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                if ($model->type == 2) {
//                                    return "后台账号：<br>" . $model->bMember->username . "<br>" . Member::$typeExplain[$model->type] . "<br>" . Member::$virtual_array[$model->is_virtual];
//                                } else {
//                                    return Member::$typeExplain[$model->type] . "<br>" . Member::$virtual_array[$model->is_virtual];
//                                }
//
//                            },
//                        ],
//                        [
//                            'attribute' => 'realname',
//                            'value' => function ($model) {
//                                return !empty($model->realname) ? $model->realname : "(暂无)";
//                            },
//                            'headerOptions' => ['width' => '136px']
//                        ],
//
//                        [
//                            'headerOptions' => ['width' => '88px'],
//                            'attribute' => 'realname_status',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'realname_status', Member::$realname_status_array, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                $sh_str = "";
//                                if ($model->realname_status == 0 && isset($model->realName->status) && $model->realName->status == 0) {
//                                    $sh_str = "</br><a class='label label-info' href='" . \common\helpers\Url::to(['/member/realname-audit/index', "SearchModel[member_id]" => $model->mobile]) . "'>去审核</a>";
//                                }
//                                return '<span class="label label-' . Member::$status_color_array[$model->realname_status] . '">' . Member::$realname_status_array[$model->realname_status] . '</span>' . $sh_str;
//                            },
//                        ],

//                        [
//                            'attribute' => 'nickname',
//                            'headerOptions' => ['class' => 'col-md-1'],
//                        ],
                        [
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'remark',
                            'editableOptions' => [
                                'asPopover' => true,
                                'inputType' => \kartik\editable\Editable::INPUT_TEXTAREA,//只需添加如下代码
                                'options' => [
                                    'rows' => 4,
                                ],
                            ],
                            'value' => function ($model) {
                                if (!empty($model->remark) && mb_strlen($model->remark) > 8) {
                                    return mb_substr($model->remark, 0, 8, 'utf-8') . "..";
                                } else {
                                    return $model->remark;
                                }

                            },
                            'headerOptions' => ['width' => '108px']
                        ],
                        [
                            'headerOptions' => ['class' => 'col-md-1'],
                            'class' => 'kartik\grid\EditableColumn',
                            'attribute' => 'promo_code'
                        ],
                        [
                            'label' => '等级/经验值',
                            'attribute' => 'memberLevel.level',
                            'filter' => Html::activeDropDownList($searchModel, 'memberLevel.level', $memberLevel, [
                                    'prompt' => '全部',
                                    'class' => 'form-control'
                                ]
                            ),
                            'format' => 'raw',
                            'value' => function ($model) {
                                $level = Html::tag('span', $model->memberLevel->name ?? '(暂无)', [
                                    'class' => 'label label-primary'
                                ]);
                                return "<div style='text-align: center;'>" . $level . '<br>' . '<br>' . "经验值：" . abs($model->account->experience) . "</div>";
                            },
                        ],

//                        [
//                            'label' => 'VIP等级',
//                            'attribute' => 'sellerLevel.level',
//                            'filter' => Html::activeDropDownList($searchModel, 'sellerLevel.level', $memberLevel, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'format' => 'raw',
//                            'value' => function ($model) {
//                                $level = Html::tag('span', $model->sellerLevel->translation->title ?? '(暂无)', [
//                                    'class' => 'label label-primary'
//                                ]);
//                                return "<div style='text-align: center;'>" . $level . "</div>";
//                            },
//                        ],

                        [
                            'label' => '账户金额',
                            'attribute' => 'recharge_money',
//                            'filter' => Html::activeDropDownList($searchModel, 'recharge_money', [1 => "已充值", 2 => "未充值"], [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
                            'value' => function ($model) {
//                                if ($model->recharge_money > 0) {
//                                    $recharge_status = "已充值";
//                                } else {
//                                    $recharge_status = "未充值";
//                                }
                                return
//                                    "充值状态：" . $recharge_status . '<br>' .
//                                    "用户本金：" . $model->principal . '<br>' .
//                                    "累计充值：" . $model->recharge_money . '<br>' .
                                    "累计提现：" . $model->withdraw_money . '<br>' .
                                    "余额钱包：" . $model->account->user_money;
//                                    . '<br>' .
//                                    "余额钱包：" . $model->account->can_withdraw_money;
//                                . '<br>' .
//                                    "积分数量：" . $model->account->user_integral;
//                                    . '<br>' .
//                                    "在购金额：" . $model->account->investment_doing_money . '<br>' .
//                                    "累购金额：" . $model->account->investment_all_money . '<br>' .
//                                    "累获收益：" . $model->account->investment_income;
//                                     . '<br>' ."推荐佣金：" . $model->account->recommend_money;
                            },
                            'format' => 'raw',
                        ],
                        [
                            'label' => '账号详情',
                            'filter' => Html::activeDropDownList($searchModel, 'sign_status', Member::$sign_array, [
                                    'prompt' => '全部',
                                    'class' => 'form-control',
                                ]
                            ),
                            'value' => function ($model) {
                                $recommend_name = $model->recommendMember->mobile ?? "(暂无)";
                                if ($model->account->recommend_number == 0) {
                                    $recommend_number_html = 0;
                                } else {
                                    ;
                                    $recommend_number_html = Html::a('<span class="label label-primary">' . $model->account->recommend_number . '人</span>', "/backend/member/member/recommend?SearchModel%5Bpid%5D=" . $model->mobile);
                                }
                                return
//                                    "信用分：" . $model->credit_score . '<br>' .
                                    "登录密码：" . $model->password_hash . '<br>' .
                                    "签到状态：" . '<span class="label label-' . Member::$status_color_array[$model->sign_status] . '">' . Member::$sign_array[$model->sign_status] . '</span>' . '<br>' .
                                    "累计签到天数：" . $model->sign_days . '<br>' .
                                    "他的推荐人：" . $recommend_name . '<br>' .
                                    "已推荐人数：" . $recommend_number_html;
                            },
                            'format' => 'raw',
                            'headerOptions' => ['width' => '232px']
                        ],
                        [
                            'label' => '登录信息(注册时间段查询)',
                            'format' => 'raw',
                            'attribute' => 'created_at',
                            'filter' => \kartik\daterange\DateRangePicker::widget([
                                'model' => $searchModel,
                                'convertFormat' => true,
                                'name' => 'created_at',
                                'attribute' => 'created_at',
                                'hideInput' => true,
                                'options' => ['class' => 'form-control'],
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
                                $last_ip = !empty($model->last_ip) ? $model->last_ip : "";
                                $last_ip_name = !empty($model->last_ip) ? \common\helpers\DebrisHelper::analysisIp($last_ip) : "(暂无)";
                                return "注册时间：" . '<br>' .
                                    Yii::$app->formatter->asDatetime($model->created_at) . '<br>' .
                                    "最后登录：" . '<br>' .
                                    \common\helpers\DateHelper::dateTime($model->last_time) . '<br>' .
                                    "登录次数：" . $model->visit_count . '<br>' .
                                    "登录IP：" . $last_ip . '<br>' .
                                    $last_ip_name;
                            },
                        ],
                        [
                            'format' => 'raw',
                            'attribute' => 'register_ip',
                            'value' => function ($model) {
                                return $model['register_ip'] . "<br>" . \common\helpers\DebrisHelper::analysisIp($model['register_ip']);
                            }
                        ],
//                        [
//                            'attribute' => 'register_url',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'register_url', $registerUrl, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) use ($registerUrl) {
//                                return $registerUrl[$model->register_url];
//                            },
//                            'headerOptions' => ['width' => '114px'],
//                        ],
//                        [
//                            'attribute' => 'register_type',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'register_type', Member::$registerTypeExplain, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return Member::$registerTypeExplain[$model->register_type];
//                            },
//                            'headerOptions' => ['width' => '114px'],
//                        ],
//                        [
//                            'attribute' => 'investment_status',
//                            'format' => 'raw',
//                            'filter' => Html::activeDropDownList($searchModel, 'investment_status', Member::$investment_status_array, [
//                                    'prompt' => '全部',
//                                    'class' => 'form-control'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                return '<span class="label label-' . Member::$status_color_array[$model->investment_status] . '">' . Member::$investment_status_array[$model->investment_status] . '</span>';
//                            },
//                            'headerOptions' => ['width' => '88px'],
//                        ],
//                        [
//                            'label' => '未购天数',
//                            'attribute' => 'investment_time',
//                            'filter' => Html::activeTextInput($searchModel, 'investment_time', [
//                                    'class' => 'form-control',
//                                    'placeholder' => '请输入'
//                                ]
//                            ),
//                            'value' => function ($model) {
//                                if ($model->investment_time == 0) {
//                                    return '<span class="label label-danger">从未购买</span>';
//                                } else {
//                                    return '<span class="label label-success">' . \common\helpers\BcHelper::div(time() - $model->investment_time, 86400, 0) . '天</span>';
//                                }
//                            },
//                            'format' => 'raw',
//                            'headerOptions' => ['width' => '85px'],
//                        ],

                        [
                            'headerOptions' => ['width' => '50px'],
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
                            'header' => "操作",
                            'contentOptions' => ['class' => 'text-align-center'],
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{recommend} {ajax-edit} {recharge} {edit}',
                            'buttons' => [
                                'coupon' => function ($url, $model, $key) {
                                    return Html::linkButton(['coupon', 'id' => $model->id], '送券', [
                                            'class' => 'green',
                                        ]) . '<br>';

                                },
                                'zhuantou' => function ($url, $model, $key) {
                                    return Html::linkButton(['/tea/investment-bill/zhuantou', 'member_id' => $model->id, 'investment_amount' => 0], '转购', [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class' => 'red',
                                        ]) . '<br>';

                                },
                                'recommend' => function ($url, $model, $key) {
                                    return Html::a('代理', ['recommend-relations', 'id' => $model->id], [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModalLg',
                                            'class' => 'purple'
                                        ]) . '<br>';
                                },
                                'ajax-edit' => function ($url, $model, $key) {
                                    return Html::a('账密', ['ajax-edit', 'id' => $model->id], [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class' => 'blue'
                                        ]) . '<br>';
                                },
                                'recharge' => function ($url, $model, $key) {
                                    return Html::a('充值', ['recharge', 'id' => $model->id], [
                                            'data-toggle' => 'modal',
                                            'data-target' => '#ajaxModal',
                                            'class' => 'orange'
                                        ]) . '<br>';
                                },
                                'edit' => function ($url, $model, $key) {
                                    return Html::a('编辑', ['edit', 'id' => $model->id], [
                                            'class' => 'green'
                                        ]) . '<br>';
                                },
                                'destroy' => function ($url, $model, $key) {
                                    return Html::a('删除', ['destroy', 'id' => $model->id], [
                                            'onclick' => "rfDelete(this);return false;",
                                            'class' => 'red',
                                        ]) . '<br>';
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>
</div>