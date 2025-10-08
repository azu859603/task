<?php

namespace backend\modules\tea\controllers;

use backend\controllers\BaseController;
use backend\modules\tea\search\ChatLogSearch;
use common\traits\Curd;
use Yii;
use common\models\tea\ChatLog;

/**
* ChatLog
*
* Class ChatLogController
* @package backend\modules\room\controllers
*/
class ChatLogController extends BaseController
{
    use Curd;

    /**
    * @var ChatLog
    */
    public $modelClass = ChatLog::class;


    /**
     *  首页
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ChatLogSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'msgTypeExplain' => ChatLog::$msgTypeExplain,
            'isReadExplain' => ChatLog::$isReadExplain,
            'statusExplain' => ChatLog::$statusExplain,
            'toTypeExplain' => ChatLog::$toTypeExplain,
            'fromTypeExplain' => ChatLog::$fromTypeExplain
        ]);
    }
}
