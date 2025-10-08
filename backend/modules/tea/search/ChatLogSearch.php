<?php

namespace backend\modules\tea\search;

use common\models\member\Member;
use common\models\tea\ChatLog;
use yii\data\ActiveDataProvider;
use Yii;

class ChatLogSearch extends ChatLog
{
    public $receiver_name;
    public $sender_name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['from_id', 'from_type', 'to_id', 'to_type', 'status', 'manager_id', 'is_read', 'msg_type'], 'integer'],
            [['content', 'created_at', 'receiver_name', 'sender_name'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = self::find();
        $page_count = isset($params['per-page']) ? $params['per-page'] : 10;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            //搜索结果分页
            'pagination' => [
                'pageSize' => $page_count,
                'pageSizeParam' => false,//隐藏pre-page
                'pageParam' => 'p',//修改page为p
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }
        $dataProvider->sort->defaultOrder = ['id' => SORT_DESC];

        if ($this->created_at) {
            $between_time = explode('~', $this->created_at);
            $first_time = strtotime($between_time[0]);
            $last_time = strtotime($between_time[1]) + 86399;
            $query->andWhere('rf_chat_log_0' . ".created_at >= {$first_time} AND " . 'rf_chat_log_0' . ".created_at <= {$last_time}");
        }

        if ($this->receiver_name) {
            $query->joinWith('receiverManager as a')
                ->joinWith('receiverMember as b')
                ->andFilterWhere(['or',
                    ['and', ['like', 'a.nickname', $this->receiver_name], ['to_type' => self::ROLE_TYPE_MANAGER]],
                    ['and', ['like', 'b.nickname', $this->receiver_name], ['to_type' => self::ROLE_TYPE_MEMBER]],
                ]);
        }

        if ($this->sender_name) {
            $query->joinWith('senderManager as c')
                ->joinWith('senderMember as d')
                ->andFilterWhere(['or',
                    ['and', ['like', 'c.nickname', $this->sender_name], ['from_type' => self::ROLE_TYPE_MANAGER]],
                    ['and', ['like', 'd.nickname', $this->sender_name], ['from_type' => self::ROLE_TYPE_MEMBER]],
                ]);
        }

        $query->andFilterWhere(['and',
            ['like', 'content', $this->content],
            ['from_type' => $this->from_type],
            ['to_type' => $this->to_type],
            [self::tableName() . '.status' => $this->status],
            ['is_read' => $this->is_read],
            ['msg_type' => $this->msg_type],
        ]);

        $backend_id = Yii::$app->user->identity->getId();
        if ($backend_id != 1) {
            $dataProvider->query->andFilterWhere([
                'or',
                ['from_type' => 1, 'from_id' => $backend_id],
                ['from_type' => 2, 'to_id' => $backend_id],
            ]);
        }

        return $dataProvider;
    }
}