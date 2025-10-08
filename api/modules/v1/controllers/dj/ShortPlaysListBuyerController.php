<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\dj;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\dj\BuyLevelList;
use common\models\dj\CollectList;
use common\models\dj\LaberList;
use common\models\dj\LikeList;
use common\models\dj\Orders;
use common\models\dj\RecentlyViewed;
use common\models\dj\SellerAvailableList;
use common\models\dj\SellerLevel;
use common\models\dj\ShortPlaysDetail;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\tea\InvestmentBill;
use yii\data\ActiveDataProvider;
use Yii;
use yii\db\Expression;
use yii\helpers\Json;

class ShortPlaysListBuyerController extends OnAuthController
{
    public $modelClass = ShortPlaysList::class;

    // 不用进行登录验证的方法
    protected $authOptional = ['list', 'hot', 'laber-list'];

    /**
     * 首页热门列表
     * @return ActiveDataProvider
     */
    public function actionHot()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['is_top' => StatusEnum::ENABLED, 'status' => StatusEnum::ENABLED])
                ->orderBy(new Expression('rand()'))
                ->with(['translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 标签列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionLaberList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);

        $model = LaberList::find()
            ->where(['status' => 1])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->with([
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                },
            ])
            ->asArray()
            ->all();
        return $model;
    }

    /**
     * 短剧列表
     * @return array|mixed|ActiveDataProvider
     */
    public function actionList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $pid = Yii::$app->request->get('pid');
        $keyword = Yii::$app->request->get('keyword');
        if (empty($pid)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "父级不能为空");
        }
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['status' => StatusEnum::ENABLED])
                ->andWhere('JSON_SEARCH(label,"one",:value) IS NOT NULL', [':value' => $pid])
//                ->with([
//                    'translation' => function ($query) use ($lang) {
//                        $query->where(['lang' => $lang]);
//                    }
//                ])
                ->joinWith([
                    'translation' => function ($query) use ($lang, $keyword) {
                        $query->where(['lang' => $lang]);
                        if ($keyword) {
                            $query->where(['like', 'title', "%" . $keyword . "%", false]);
                        }
                    },
                ])
//                ->orderBy(['id' => SORT_DESC])
                ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 买家短剧详情
     * @return array|mixed|\yii\db\ActiveRecord|null
     */
    public function actionDetail()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $id = Yii::$app->request->get('id');
//        $memberInfo = Member::find()->where(['id' => $this->memberId])->select(['pid'])->asArray()->one();
//        if (empty($memberInfo['pid'])) {
//            $pid = 1;
//        } else {
//            $pid = $memberInfo['pid'];
//        }
        $model = ShortPlaysList::find()
            ->where(['id' => $id])
            ->with([
//                'sellerAvailableList' => function ($query) use ($pid) {
//                    $query->where(['member_id' => $pid]);
//                },
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                },
                'shortPlaysDetails' => function ($query) use ($lang) {
                    $query->select(['id', 'pid', 'type'])
                        ->with([
                            'translation' => function ($query) use ($lang) {
//                                $query->where(['lang' => $lang]);
                                $query->where(['lang' => 'cn']);
                            },
                            'likeList' => function ($query) {
                                $query->select(['id', 'pid'])->where(['member_id' => $this->memberId]);
                            },
                            'collectList' => function ($query) {
                                $query->select(['id', 'pid'])->where(['member_id' => $this->memberId]);
                            },

                        ]);
                }
            ])
            ->asArray()
            ->one();
        if (empty($model)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "信息错误");
        }
        // 如果该剧集已被解锁过
        if (Orders::find()->where(['member_id' => $this->memberId, 'key_status' => 1, 'pid' => $model['id']])->exists()) {
            $model['is_open'] = 1;
        } else {
            $model['is_open'] = 0;
            foreach ($model['shortPlaysDetails'] as $k => $v) {
                if ($v['type'] == 1) {
                    $model['shortPlaysDetails'][$k]['translation']['content'] = "";
                }
            }
        }
        //点击进入详情，添加观看次数
        ShortPlaysList::updateAllCounters(['number' => 1], ['id' => $id]);
        return $model;
    }

    /**
     * 最近观看列表
     * @return ActiveDataProvider
     */
    public function actionRecentlyViewed()
    {
        $keyword = Yii::$app->request->get('keyword');
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => RecentlyViewed::find()
                ->where(['dj_recently_viewed.member_id' => $this->memberId])
                ->orderBy('created_at desc')
                ->joinWith([
                    'shortPlaysDetail' => function ($query) use ($lang, $keyword) {
                        $query->joinWith([
                            'shortPlaysList' => function ($query) use ($lang, $keyword) {
                                $query->joinWith([
                                    'translation' => function ($query) use ($lang, $keyword) {
                                        $query->where(['dj_short_plays_list_translations.lang' => $lang]);
                                        if ($keyword) {
                                            $query->where(['like', 'dj_short_plays_list_translations.title', "%" . $keyword . "%", false]);
                                        }
                                    },
                                ]);
                            },
                            'translation' => function ($query) use ($lang, $keyword) {
//                                $query->where(['dj_short_plays_detail_translations.lang' => $lang]);
                                $query->where(['dj_short_plays_detail_translations.lang' => 'cn']);
                            },
                        ]);
                    },
                ])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 我的最爱列表
     * @return ActiveDataProvider
     */
    public function actionLikeList()
    {
        $keyword = Yii::$app->request->get('keyword');
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => LikeList::find()
                ->where(['dj_like_list.member_id' => $this->memberId])
                ->orderBy('created_at desc')
                ->joinWith([
                    'shortPlaysDetail' => function ($query) use ($lang, $keyword) {
                        $query->joinWith([
                            'shortPlaysList' => function ($query) use ($lang, $keyword) {
                                $query->joinWith([
                                    'translation' => function ($query) use ($lang, $keyword) {
                                        $query->where(['dj_short_plays_list_translations.lang' => $lang]);
                                        if ($keyword) {
                                            $query->where(['like', 'dj_short_plays_list_translations.title', "%" . $keyword . "%", false]);
                                        }
                                    },
                                ]);
                            },
                            'translation' => function ($query) use ($lang, $keyword) {
//                                $query->where(['dj_short_plays_detail_translations.lang' => $lang]);
                                $query->where(['dj_short_plays_detail_translations.lang' => 'cn']);
                            },
                        ]);
                    },
                ])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 我的收藏列表
     * @return ActiveDataProvider
     */
    public function actionCollectList()
    {
        $keyword = Yii::$app->request->get('keyword');
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => CollectList::find()
                ->where(['dj_collect_list.member_id' => $this->memberId])
                ->orderBy('created_at desc')
                ->joinWith([
                    'shortPlaysDetail' => function ($query) use ($lang, $keyword) {
                        $query->joinWith([
                            'shortPlaysList' => function ($query) use ($lang, $keyword) {
                                $query->joinWith([
                                    'translation' => function ($query) use ($lang, $keyword) {
                                        $query->where(['dj_short_plays_list_translations.lang' => $lang]);
                                        if ($keyword) {
                                            $query->where(['like', 'dj_short_plays_list_translations.title', "%" . $keyword . "%", false]);
                                        }
                                    },
                                ]);
                            },
                            'translation' => function ($query) use ($lang, $keyword) {
//                                $query->where(['dj_short_plays_detail_translations.lang' => $lang]);
                                $query->where(['dj_short_plays_detail_translations.lang' => 'cn']);
                            },
                        ]);
                    },
                ])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 添加最近观看记录
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionAddRecentlyViewed()
    {
        RedisHelper::verify($this->memberId, $this->action->id, 1);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }

        $model = RecentlyViewed::find()->where(['member_id' => $this->memberId, 'pid' => $id])->one();
        if (empty($model)) {
            $model = new RecentlyViewed();
            $model->member_id = $this->memberId;
            $model->pid = $id;
        }
        $model->created_at = time();
        $model->save();

        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }

    /**
     * 添加点赞记录
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionAddLikeList()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }


        $model = LikeList::find()->where(['member_id' => $this->memberId, 'pid' => $id])->one();
        if (empty($model)) {
            $model = new LikeList();
            $model->member_id = $this->memberId;
            $model->pid = $id;
            $model->save();
            ShortPlaysDetail::updateAllCounters(['like_number' => 1], ['id' => $id]);
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }

    /**
     * 取消点赞记录
     * @return array|mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionUnLikeList()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }

        $model = LikeList::find()
            ->where(['member_id' => $this->memberId, 'pid' => $id])
            ->one();
        if (empty(!$model)) {
            $model->delete();
            ShortPlaysDetail::updateAllCounters(['like_number' => -1], ['id' => $id]);
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }


    /**
     * 添加收藏记录
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionAddCollectList()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }


        $model = CollectList::find()->where(['member_id' => $this->memberId, 'pid' => $id])->one();
        if (empty($model)) {
            $model = new CollectList();
            $model->member_id = $this->memberId;
            $model->pid = $id;
            $model->save();
            ShortPlaysDetail::updateAllCounters(['collect_number' => 1], ['id' => $id]);
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }

    /**
     * 取消收藏记录
     * @return array|mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionUnCollectList()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $id = Yii::$app->request->post('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }

        $model = CollectList::find()
            ->where(['member_id' => $this->memberId, 'pid' => $id])
            ->one();
        if (empty(!$model)) {
            $model->delete();
            ShortPlaysDetail::updateAllCounters(['collect_number' => -1], ['id' => $id]);
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "OK");
    }


    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}