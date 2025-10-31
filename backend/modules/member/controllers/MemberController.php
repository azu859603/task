<?php

namespace backend\modules\member\controllers;

use backend\modules\member\forms\BuyerForm;
use backend\modules\member\forms\ExportForm;
use backend\modules\member\forms\ExportMoneyAllForm;
use backend\modules\member\forms\ExportMoneyForm;
use backend\modules\member\forms\RecommendForm;
use backend\modules\tea\forms\CouponForm;
use common\helpers\ArrayHelper;
use common\helpers\BcHelper;
use common\helpers\ExcelHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\member\Account;
use common\models\member\RechargeBill;
use common\models\member\WithdrawBill;
use common\models\tea\CouponList;
use common\models\tea\CouponMember;
use Yii;
use common\models\base\SearchModel;
use common\traits\MerchantCurd;
use common\models\member\Member;
use common\enums\StatusEnum;
use backend\controllers\BaseController;
use backend\modules\member\forms\RechargeForm;
use yii\helpers\Json;
use yii\web\Response;

/**
 * 会员管理
 *
 * Class MemberController
 * @package backend\modules\member\controllers
 * @author 原创脉冲
 */
class MemberController extends BaseController
{
    use MerchantCurd;

    /**
     * @var \yii\db\ActiveRecord
     */
    public $modelClass = Member::class;

    /**
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = Member::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Member'));
            $post = ['Member' => $posted];
            if ($model->load($post) && $model->save()) {
                if ($attribute == 'remark') {
                    if (!empty($model->$attribute) && mb_strlen($model->$attribute) > 8) {
                        $output = mb_substr($model->$attribute, 0, 8, 'utf-8') . "..";
                    }
                } elseif ($attribute == 'status') {
                    if ($model->status == 0) {
                        $model->online_status = 0;
                        $model->save();
                    }
                    $output = ['1' => '启用', '0' => '禁用'][$model->status];
                } else {
                    $output = $model->$attribute;
                }
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'relations' => ['memberLevel' => ['level']],
//                'relations' => ['sellerLevel' => ['level']],
                'partialMatchAttributes' => ['realname', 'mobile', 'register_ip'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);


//            $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);


            $dataProvider->query
                ->andWhere(['>=', 'rf_member.status', StatusEnum::DISABLED])
                ->andWhere(['rf_member.is_virtual' => 0])
                ->andFilterWhere(['rf_member.merchant_id' => $this->getMerchantId()])
//                ->with(['account', 'memberLevel']);
                ->with([
                    'account',
                    'memberLevel',
                    'sellerLevel' => function ($query) use ($lang) {
                        $query->with([
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            }]);
                    }
                ]);

//            $backend_id = Yii::$app->user->identity->getId();
//            if ($backend_id != 1) {
//                $a_id = Yii::$app->user->identity->aMember->id;
//                $childrenIds = Member::getChildrenIds($a_id);
//                $dataProvider->query->andFilterWhere(['in', 'rf_member.id', $childrenIds]);
//            }

            $memberLevel = yii\helpers\ArrayHelper::map(\common\models\member\Level::find()->orderBy(['level' => SORT_ASC])->asArray()->all(), 'level', 'name');

//            $memberLevels = \common\models\dj\SellerLevel::find()->with(['translation' => function ($query) use ($lang) {
//                $query->where(['lang' => $lang]);
//            }])
//                ->asArray()
//                ->all();
//            foreach ($memberLevels as $k => $v) {
//                $id = $v['level'];
//                $memberLevel[$id] = !empty($v['translation']['title']) ? $v['translation']['title'] : "暂无";
//            }


            $registerUrl = \yii\helpers\ArrayHelper::map(\common\models\member\Member::find()->select(['register_url'])->groupBy(['register_url'])->asArray()->all(), 'register_url', 'register_url');
            return $this->render($this->action->id, [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'memberLevel' => $memberLevel,
                'registerUrl' => $registerUrl,
            ]);
        }
    }

    /**
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionVirtual()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = Member::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Member'));
            $post = ['Member' => $posted];
            if ($model->load($post) && $model->save()) {
                if ($attribute == 'remark') {
                    if (!empty($model->$attribute) && mb_strlen($model->$attribute) > 8) {
                        $output = mb_substr($model->$attribute, 0, 8, 'utf-8') . "..";
                    }
                } elseif ($attribute == 'status') {
                    if ($model->status == 0) {
                        $model->online_status = 0;
                        $model->save();
                    }
                    $output = ['1' => '启用', '0' => '禁用'][$model->status];
                } else {
                    $output = $model->$attribute;
                }
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
//                'relations' => ['memberLevel' => ['level']],
                'relations' => ['sellerLevel' => ['level']],
                'partialMatchAttributes' => ['realname', 'mobile', 'register_ip'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);


//            $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->andWhere(['>=', 'rf_member.status', StatusEnum::DISABLED])
                ->andWhere(['rf_member.is_virtual' => 1])
                ->andFilterWhere(['rf_member.merchant_id' => $this->getMerchantId()])
//                ->with(['account', 'memberLevel']);
                ->with([
                    'account',
                    'memberLevel',
                    'sellerLevel' => function ($query) use ($lang) {
                        $query->with([
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            }]);
                    }
                ]);

//            $backend_id = Yii::$app->user->identity->getId();
//            if ($backend_id != 1) {
//                $a_id = Yii::$app->user->identity->aMember->id;
//                $childrenIds = Member::getChildrenIds($a_id);
//                $dataProvider->query->andFilterWhere(['in', 'rf_member.id', $childrenIds]);
//            }

//            $memberLevel = yii\helpers\ArrayHelper::map(\common\models\member\Level::find()->orderBy(['level' => SORT_ASC])->asArray()->all(), 'level', 'name');

            $memberLevels = \common\models\dj\SellerLevel::find()->with(['translation' => function ($query) use ($lang) {
                $query->where(['lang' => $lang]);
            }])
                ->asArray()
                ->all();
            foreach ($memberLevels as $k => $v) {
                $id = $v['level'];
                $memberLevel[$id] = !empty($v['translation']['title']) ? $v['translation']['title'] : "暂无";
            }


            $registerUrl = \yii\helpers\ArrayHelper::map(\common\models\member\Member::find()->select(['register_url'])->groupBy(['register_url'])->asArray()->all(), 'register_url', 'register_url');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'memberLevel' => $memberLevel,
                'registerUrl' => $registerUrl,
            ]);
        }
    }

    /**
     * 详情
     * @param $id
     * @return string
     */
    public function actionView($id)
    {
        $model = Member::find()->where(['id' => $id])->with(['account', 'memberLevel'])->one();
        $recharge_money = RechargeBill::find()->where(['member_id' => $id, 'status' => StatusEnum::ENABLED])->sum('recharge_money') ?? "0.00";
        $withdraw_money = WithdrawBill::find()->where(['member_id' => $id, 'status' => StatusEnum::ENABLED])->sum('withdraw_money') ?? "0.00";
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'recharge_money' => $recharge_money,
            'withdraw_money' => $withdraw_money,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);
        $model->merchant_id = !empty($this->getMerchantId()) ? $this->getMerchantId() : 0;
        $model->scenario = 'backendCreate';
        $modelInfo = clone $model;

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            // 验证密码
//            if ($modelInfo['password_hash'] != $model->password_hash) {
//                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password_hash);
//            }

            return $model->save()
                ? $this->message("操作成功", $this->redirect(['index']))
                : $this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 积分/余额变更
     *
     * @param $id
     * @return mixed|string
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    public function actionRecharge($id)
    {
        $rechargeForm = new RechargeForm();
        $member = $this->findModel($id);
        // ajax 校验
        $this->activeFormValidate($rechargeForm);
        if ($rechargeForm->load(Yii::$app->request->post())) {
            if ($rechargeForm->type == RechargeForm::TYPE_EXPERIENCE) {
                if ($rechargeForm->change == 1) { // 增加
                    $member->account->experience = BcHelper::add($member->account->experience, $rechargeForm->experience);
                } else {
                    $experience = BcHelper::sub($member->account->experience, $rechargeForm->experience);
                    if ($experience < 0) {
                        $experience = 0;
                    }
                    $member->account->experience = $experience;
                }
                $member->account->save(false);
                // 更新等级
                Yii::$app->services->memberLevel->updateLevel($member);
                // 添加操作日志
                Yii::$app->services->actionLog->create('member/recharge', '充值经验');
            } else {
                if (!$rechargeForm->save($member)) {
                    return $this->message($this->getError($rechargeForm), $this->redirect(['index']), 'error');
                } else {
                    // 添加统计
                    if ($member['type'] == 1 && $rechargeForm->change == $rechargeForm::CHANGE_INCR) {
                        // 统计充值金额
                        if ($rechargeForm->type == $rechargeForm::TYPE_MONEY) {
                            // 加入统计表 获取最上级用户ID
                            $first_member = Member::getParentsFirst($member);
                            $b_id = $first_member['b_id'] ?? 0;
                            Statistics::updateRechargeMoney(date("Y-m-d"), $rechargeForm->money, $member['id'], $b_id);
                        }
                    }

                    // 添加个人充值统计
                    if ($rechargeForm->change == $rechargeForm::CHANGE_INCR && $rechargeForm->type == $rechargeForm::TYPE_MONEY) {
                        $member->principal = BcHelper::add($member->principal, $rechargeForm->money);
                        $member->recharge_money = BcHelper::add($member->recharge_money, $rechargeForm->money);
                        $member->save(false);
                    }
                }
            }
            // 添加操作日志
            Yii::$app->services->actionLog->create('member/recharge', '充值');
            return $this->message('充值成功', $this->redirect(['index']));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $member,
            'rechargeForm' => $rechargeForm,
        ]);
    }


    /**
     * 编辑/创建
     * @return mixed|string
     * @throws \yii\base\Exception
     */
    public function actionEdit()
    {
        $id = Yii::$app->request->get('id', null);
        $model = $this->modelClass::find()
            ->where(['id' => $id])
            ->with(['account'])
            ->one();
        if ($model->load(Yii::$app->request->post()) && $model->account->load(Yii::$app->request->post())) {
//            var_dump($model->b_id);exit;
            if (!empty($model->safety_password)) {
                $model->safety_password_hash = Yii::$app->security->generatePasswordHash($model->safety_password);
            }
            if (!empty($model->realname)) {
                $model->realname_status = 1;
            } else {
                $model->realname_status = 0;
            }
            if ($model->save() && $model->account->save()) {
                // 添加操作日志
                Yii::$app->services->actionLog->create('member/edit', '编辑用户信息');
                return $this->message("编辑成功", $this->redirect(['index']));
            } else {
                return $this->message($this->getError($model), $this->redirect(['index']), 'error');
            }
        }
        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     *  代理关系
     * @param $id
     * @return string
     */
    public function actionRecommendRelations($id)
    {
        $member = Member::find()->with(['account' => function ($query) {
            return $query->select('user_money');
        }])->select('id,mobile as title,pid,status')->where(['id' => $id])->asArray()->one();
        $member['title'] .= '(余额:' . $member['account']['user_money'] . ')';
        if ($member['status'] == 0) {
            $member['title'] .= '(<font style=\"color: red\">已封停</font>)';
        }
        $parents = Member::getParents($member);
        $children = Member::find()->select('id,mobile as title,pid')->where(['pid' => $id])->asArray()->all();
        $children[] = $member;

        //拿到所有的上级id
        $parentsIds = [];
        foreach ($parents as $k => $v) {
            $parentsIds[$k] = $v['id'];
            if ($v['status'] == 0 && $v['id'] != $id) {
                $parents[$k]['title'] .= '(<font style=\"color: red\">已封停</font>)';
            }
        }

        //拿到所有下级id
        $childrenIds = [];
        foreach ($children as $k => $v) {
            $childrenIds[$k] = $v['id'];
        }

        // 递归查询下级总人数
        $children_all = Member::find()->select(['id'])->where(['pid' => $id])->asArray()->all();
        $number = 0;
        $children_number = Member::getMemberNumber($number, $children_all);

        return $this->renderAjax('recommend-relations', [
            'parents' => $parents,
            'parentsIds' => $parentsIds,
            'children' => $children,
            'childrenIds' => $childrenIds,
            'cid' => $id,
            'children_number' => $children_number,
        ]);
    }

    /**
     *  获取根节点
     * @param $id
     * @return string
     */
    public function actionGetRoot($id)
    {
        $member = Member::find()
            ->with(['account' => function ($query) {
                return $query->select('user_money');
            }])
            ->select('id,mobile as text,pid as parent,status')
            ->where(['id' => $id])
            ->asArray()
            ->one();
        $member['text'] .= '(余额:' . $member['account']['user_money'] . ')';
        if ($member['status'] == 0) {
            $member['text'] .= '(' . "<font style='color: red'>已封停</font>" . ')';
        }
        $member['parent'] = '#';
        $member['children'] = true;
        return Json::encode($member);
    }

    /**
     * 获取下级
     * @param $id
     * @return string
     */
    public function actionGetChildren($id)
    {
        $children = Member::find()
            ->select('id,mobile as text,pid as parent,status,sign_days')
            ->where(['pid' => $id])
            ->with([
                'account' => function ($query) {
                    return $query->select('member_id,user_money');
                },
                'card',
            ])
            ->asArray()
            ->all();
        foreach ($children as $k => $v) {
            $children[$k]['children'] = true;
            $children[$k]['text'] .= '(余额:' . $v['account']['user_money'] . ')';
            if ($children[$k]['status'] == 0) {
                $children[$k]['text'] .= '(' . "<font style='color: red'>已封停</font>" . ')';
            }
        }
        return Json::encode($children);
    }

    /**
     * 一键封停/解封
     */
    public function actionBlock()
    {
        $posts = Yii::$app->request->post();
        $status = $posts['status'];
        $remark = $posts['remark'];
        if (!empty($posts['childrenIds']) && !empty($posts['parentsIds'])) {
            $idsArr = array_unique(ArrayHelper::merge($posts['childrenIds'], $posts['parentsIds']));
        } elseif (isset($posts['childrenIds']) && !empty($posts['childrenIds'])) {
            $idsArr = ArrayHelper::getValue($posts, 'childrenIds');
        } elseif (isset($posts['parentsIds']) && !empty($posts['parentsIds'])) {
            $idsArr = ArrayHelper::getValue($posts, 'parentsIds');
        }
        foreach ($idsArr as $id) {
            $children = Member::find()->where(['id' => $id])->one();
            $children->status = $status;
            if (!empty($remark)) {
                if (!empty($children->remark)) {
                    $children->remark = $children->remark . "；" . $remark;
                } else {
                    $children->remark = $remark;
                }
            }
            $children->save();
        }
        Yii::$app->session->setFlash('success', '封停成功');
        return ResultHelper::json(200, 'ok');
    }

    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     * @author 哈哈
     */
    public function actionRecommend()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['realname', 'mobile'], // 模糊查询
            'relations' => ['account' => ['investment_number'],],
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);
        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['<>', 'pid', 0])
//            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->with(['recommendMember', 'account']);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * ajax编辑/创建
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEditRecommend()
    {
        $model = new RecommendForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $member = Member::find()
                ->where(['id' => $model->id, 'pid' => 0])
                ->orWhere(['id' => $model->id, 'pid' => null])
                ->one(); // 下级
            if (empty($member)) {
                return $this->message('添加失败，找不到该用户或该用户已经有上级！', $this->redirect(Yii::$app->request->referrer), 'error');
            }
            if ($model->id == $model->pid) {
                return $this->message('添加失败，上线和下线不能是同一个人！', $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $member2 = Member::find()->where(['id' => $model->pid])->one(); // 上级
            if ($member2->pid == $member->id) {
                return $this->message('添加失败，不能互为上下级！', $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $member->pid = $model->pid;
            if ($member->save(false) && $member->type == 1) {
                // 推荐人数+1
                $recommendAccount = Account::findOne(['member_id' => $member->pid]);
                $recommendAccount->recommend_number += 1;
                $recommendAccount->save(false);
                return $this->message('添加成功', $this->redirect(Yii::$app->request->referrer));
            } else {
                return $this->message('添加失败', $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    /**
     * 获取用户
     * @return array
     */
    public function actionGetUser()
    {

        $page = Yii::$app->request->get('page', 1) - 1;
        $page_size = 20;
        $q = Yii::$app->request->get('q', '');
        $query = Member::find()->where(['status' => StatusEnum::ENABLED]);
        $countQuery = clone $query;
        if ($q) {
            $query->andFilterWhere(['like', 'mobile', $q]);
        }

        $p = Yii::$app->request->get('p', '');
        if (isset($p)) {
            $query->andFilterWhere(['pid' => $p]);
        }

        $t = Yii::$app->request->get('t', '');
        if (isset($t)) {
            $query->andFilterWhere(['type' => $t]);
        }
        $v = Yii::$app->request->get('v', '');
        if (isset($v)) {
            $query->andFilterWhere(['is_virtual' => $v]);
        }

        $member = $query
            ->offset($page * $page_size)
            ->asArray()
            ->select(['id', 'mobile'])
            ->limit($page_size)
            ->orderBy(['id' => SORT_DESC])
            ->all();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, $query->count(), $member);
    }


    /**
     * 获取买家
     * @return array
     */
    public function actionGetMember()
    {
        $page = Yii::$app->request->get('page', 1) - 1;
        $page_size = 20;
        $q = Yii::$app->request->get('q', '');
        $query = Member::find()->where(['status' => StatusEnum::ENABLED, 'type' => 0]);
        $countQuery = clone $query;
        if ($q) {
            $query->andFilterWhere(['like', 'mobile', $q]);
        }
        $member = $query
            ->offset($page * $page_size)
            ->asArray()
            ->select(['id', 'mobile'])
            ->limit($page_size)
            ->orderBy(['id' => SORT_DESC])
            ->all();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, $countQuery->count(), $member);
    }

    /**
     * 获取卖家
     * @return array
     */
    public function actionGetSeller()
    {
        $page = Yii::$app->request->get('page', 1) - 1;
        $page_size = 20;
        $q = Yii::$app->request->get('q', '');
        $query = Member::find()->where(['status' => StatusEnum::ENABLED, 'type' => 1]);
        $countQuery = clone $query;
        if ($q) {
            $query->andFilterWhere(['like', 'mobile', $q]);
        }
        $member = $query
            ->offset($page * $page_size)
            ->asArray()
            ->select(['id', 'mobile'])
            ->limit($page_size)
            ->orderBy(['id' => SORT_DESC])
            ->all();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, $countQuery->count(), $member);
    }

    /**
     * 获取用户
     * @return array
     */
    public function actionGetBackendUser()
    {
        $page = Yii::$app->request->get('page', 1) - 1;
        $page_size = 20;
        $q = Yii::$app->request->get('q', '');
        $query = \common\models\backend\Member::find()->where(['status' => StatusEnum::ENABLED]);
        $countQuery = clone $query;
        if ($q) {
            $query->andFilterWhere(['like', 'username', $q]);
        }
        $member = $query
            ->offset($page * $page_size)
            ->asArray()
            ->select(['id', 'username'])
            ->limit($page_size)
            ->orderBy(['id' => SORT_DESC])
            ->all();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, $countQuery->count(), $member);
    }

    /**
     * 导出
     */
    public function actionExport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new ExportForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->level == 0) { // 全部
                $where_array = [];
            } else {
                $where_array = ['current_level' => $model->level];
            }
            $models = Member::find()
                ->select(['id', 'principal', 'mobile', 'realname', 'sign_status', 'sign_days', 'created_at', 'last_time', 'visit_count', 'last_ip', 'investment_status', 'investment_time', 'current_level', 'pid',])
                ->where(['<>', 'status', '-1'])
                ->andFilterWhere($where_array)
                ->with([
                    'account' => function ($query) {
                        $query->select(['id', 'member_id', 'experience', 'user_integral', 'user_money', 'investment_doing_money', 'investment_all_money', 'investment_income', 'recommend_money', 'recommend_number']);
                    },
                    'recommendMember' => function ($query) {
                        $query->select(['id', 'mobile']);
                    },
                    'memberLevel' => function ($query) {
                        $query->select(['id', 'name', 'level']);
                    }])
                ->asArray()
                ->all();
            $header = [
                ['ID', 'id'],
                ['账号', 'mobile'],
                ['真实姓名', 'realname'],
                ['等级', 'memberLevel.name'],
                ['经验值', 'account.experience'],
                ['积分数量', 'account.user_integral'],
                ['本金', 'principal'],
                ['账户余额', 'account.user_money'],
                ['在购金额', 'account.investment_doing_money'],
                ['累购金额', 'account.investment_all_money'],
                ['累获收益', 'account.investment_income'],
                ['推荐佣金', 'account.recommend_money'],
                ['签到状态', 'sign_status', 'selectd', [1 => "已签", 0 => "未签"]],
                ['累计签到天数', 'sign_days'],
                ['他的推荐人', 'recommendMember.mobile'],
                ['已推荐人数', 'account.recommend_number'],
                ['注册时间', 'created_at', 'date', 'Y-m-d H:i:s'],
                ['登录时间', 'last_time', 'date', 'Y-m-d H:i:s'],
                ['登录次数', 'visit_count'],
                ['最后登录IP', 'last_ip'],
                ['购买状态', 'investment_status', 'selectd', [0 => "从未购买", 1 => "在购用户", 2 => "未购用户"]],
                [
                    '未购天数',
                    'investment_time',
                    'function',
                    function ($model) {
                        if ($model['investment_time'] == 0) {
                            return '从未购买';
                        } else {
                            return \common\helpers\BcHelper::div(time() - $model['investment_time'], 86400, 0) . '天';
                        }
                    },
                ],
            ];
            return ExcelHelper::exportData($models, $header, '导出会员_' . time());
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 解除上下级
     * @param $id
     * @param $pid
     * @return mixed|string
     */
    public function actionDismiss($id, $pid)
    {
        $member = Member::find()->where(['id' => $id, 'pid' => $pid])->one();
        if (empty($member)) {
            return $this->message('解除操作失败,当前已无上下级关系', $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $member->pid = 0;
        $member->save(false);
        $pid_account = Account::find()->where(['member_id' => $pid])->one();
        $pid_account->recommend_number -= 1;
        $pid_account->save(false);
        return $this->message('操作成功', $this->redirect(Yii::$app->request->referrer));
    }

    /**
     * 根据本金导出
     */
    public function actionExportMoney()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new ExportMoneyForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $models = Member::find()
                ->select(['id', 'principal', 'mobile', 'realname', 'sign_status', 'sign_days', 'created_at', 'last_time', 'visit_count', 'last_ip', 'investment_status', 'investment_time', 'current_level', 'pid',])
                ->where(['<>', 'status', '-1'])
                ->where(['between', 'principal', $model->start_money, $model->stop_money])
                ->with([
                    'account' => function ($query) {
                        $query->select(['id', 'member_id', 'experience', 'user_integral', 'user_money', 'investment_doing_money', 'investment_all_money', 'investment_income', 'recommend_money', 'recommend_number']);
                    },
                    'recommendMember' => function ($query) {
                        $query->select(['id', 'mobile']);
                    },
                    'memberLevel' => function ($query) {
                        $query->select(['id', 'name', 'level']);
                    }])
                ->asArray()
                ->all();
            $header = [
                ['ID', 'id'],
                ['账号', 'mobile'],
                ['真实姓名', 'realname'],
                ['等级', 'memberLevel.name'],
                ['经验值', 'account.experience'],
                ['积分数量', 'account.user_integral'],
                ['本金', 'principal'],
                ['账户余额', 'account.user_money'],
                ['在购金额', 'account.investment_doing_money'],
                ['累购金额', 'account.investment_all_money'],
                ['累获收益', 'account.investment_income'],
                ['推荐佣金', 'account.recommend_money'],
                ['签到状态', 'sign_status', 'selectd', [1 => "已签", 0 => "未签"]],
                ['累计签到天数', 'sign_days'],
                ['他的推荐人', 'recommendMember.mobile'],
                ['已推荐人数', 'account.recommend_number'],
                ['注册时间', 'created_at', 'date', 'Y-m-d H:i:s'],
                ['登录时间', 'last_time', 'date', 'Y-m-d H:i:s'],
                ['登录次数', 'visit_count'],
                ['最后登录IP', 'last_ip'],
                ['购买状态', 'investment_status', 'selectd', [0 => "从未购买", 1 => "在购用户", 2 => "未购用户"]],
                [
                    '未购天数',
                    'investment_time',
                    'function',
                    function ($model) {
                        if ($model['investment_time'] == 0) {
                            return '从未购买';
                        } else {
                            return \common\helpers\BcHelper::div(time() - $model['investment_time'], 86400, 0) . '天';
                        }
                    },
                ],
            ];
            return ExcelHelper::exportData($models, $header, '导出会员_' . time());
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 根据投资金额导出
     */
    public function actionExportMoneyAll()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new ExportMoneyAllForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $account_ids = Account::find()->select(['member_id'])->where(['between', 'investment_all_money', $model->start_money, $model->stop_money])->column();
            $models = Member::find()
                ->select(['id', 'principal', 'mobile', 'realname', 'sign_status', 'sign_days', 'created_at', 'last_time', 'visit_count', 'last_ip', 'investment_status', 'investment_time', 'current_level', 'pid',])
                ->where(['<>', 'status', '-1'])
                ->andWhere(['in', 'id', $account_ids])
                ->with([
                    'account' => function ($query) {
                        $query->select(['id', 'member_id', 'experience', 'user_integral', 'user_money', 'investment_doing_money', 'investment_all_money', 'investment_income', 'recommend_money', 'recommend_number']);
                    },
                    'recommendMember' => function ($query) {
                        $query->select(['id', 'mobile']);
                    },
                    'memberLevel' => function ($query) {
                        $query->select(['id', 'name', 'level']);
                    }])
                ->asArray()
                ->all();
            $header = [
                ['ID', 'id'],
                ['账号', 'mobile'],
                ['真实姓名', 'realname'],
                ['等级', 'memberLevel.name'],
                ['经验值', 'account.experience'],
                ['积分数量', 'account.user_integral'],
                ['本金', 'principal'],
                ['账户余额', 'account.user_money'],
                ['在购金额', 'account.investment_doing_money'],
                ['累购金额', 'account.investment_all_money'],
                ['累获收益', 'account.investment_income'],
                ['推荐佣金', 'account.recommend_money'],
                ['签到状态', 'sign_status', 'selectd', [1 => "已签", 0 => "未签"]],
                ['累计签到天数', 'sign_days'],
                ['他的推荐人', 'recommendMember.mobile'],
                ['已推荐人数', 'account.recommend_number'],
                ['注册时间', 'created_at', 'date', 'Y-m-d H:i:s'],
                ['登录时间', 'last_time', 'date', 'Y-m-d H:i:s'],
                ['登录次数', 'visit_count'],
                ['最后登录IP', 'last_ip'],
                ['购买状态', 'investment_status', 'selectd', [0 => "从未购买", 1 => "在购用户", 2 => "未购用户"]],
                [
                    '未购天数',
                    'investment_time',
                    'function',
                    function ($model) {
                        if ($model['investment_time'] == 0) {
                            return '从未购买';
                        } else {
                            return \common\helpers\BcHelper::div(time() - $model['investment_time'], 86400, 0) . '天';
                        }
                    },
                ],
            ];
            return ExcelHelper::exportData($models, $header, '导出会员_' . time());
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    /**
     * 赠送优惠券
     * @param $id
     * @return mixed|string
     */
    public function actionCoupon($id)
    {
        $model = new CouponForm();
        if ($model->load(Yii::$app->request->post())) {
            foreach ($model['content'] as $v) {
                if (!empty($v['value'])) {
                    $send_model = CouponList::find()->where(['id' => $v['key']])->select(['id', 'type', 'valid_date'])->asArray()->one();
                    for ($i = 0; $i < $v['value']; $i++) {
                        CouponMember::createModel($id, $send_model['id'], $send_model['type'], strtotime(date("Y-m-d")), strtotime(date("Y-m-d")) + ($send_model['valid_date'] * 86400));
                    }
                }
            }
            return $this->message("操作成功", $this->redirect(['index']));
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\Exception
     * @throws \yii\base\ExitException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionAjaxEditBuyer()
    {
        $model = new BuyerForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            // 解除内存限制
            ini_set('memory_limit', '-1');
            set_time_limit(0);

            for ($i = 0; $i < $model->number; $i++) {
                $member = new Member();
                $data = file_get_contents('email.txt');
                $data = str_replace("\r\n", "\n", trim($data));
                $data = explode("\n", $data);
                $email = $data[array_rand($data)];
                if (Member::find()->where(['mobile' => $email])->exists()) {
                    $email = Member::getEmail(rand(5, 11));
                }
                $member->mobile = $email;
                $member->pid = $model->pid;
                $member->type = 0;
                $member->is_virtual = 1;
                $member->password_hash = $model->password;
                $member->save(false);
            }

            return $this->message("操作成功", $this->redirect(['index']));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

//    public function getEmail($number)
//    {
//        $sz = range("0", "9");
//        $zm = range("a", "z");
//        $dx = range("A", "Z");
//        $all = array_merge($sz, $zm);
//        $all = array_merge($all, $dx);
//        $email_array = ['gmail', 'hotmail', 'yahoo', 'outlook', 'live', 'qq', 'foxmail', 'sina', '163', '126', 'live', 'aol', 'icloud', 'protonmail', 'zohomail'];
//        $email = $email_array[array_rand($email_array)];
//        $result = implode("", array_rand(array_flip($all), $number)) . "@" . $email . ".com";
//        if (Member::find()->where(['mobile' => $result])->exists()) {
//            $this->getEmail($number);
//        }
//        return $result;
//    }
}