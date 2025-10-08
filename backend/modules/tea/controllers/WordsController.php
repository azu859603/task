<?php


namespace backend\modules\tea\controllers;

use common\helpers\ArrayHelper;
use Yii;
use backend\controllers\BaseController;
use common\models\tea\Words;

class WordsController extends BaseController
{

    /**
     * @var Words
     */
    public $modelClass = Words::class;

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->isAjax) {
            $word = Yii::$app->request->post('word');
            $type = Yii::$app->request->post('type');
            $room_id = Yii::$app->request->post('room_id');
            if ($type == 'add') {
                $model = new Words();
                $model->word = trim($word);
                $model->room_id = $room_id;
                $model->save(false);
            } else {
                $model = Words::findOne(['word' => $word, 'room_id' => $room_id]);
                $model->delete();
            }
            Words::getWords(true, $room_id);
            return true;
        }
        $identity = Yii::$app->user->identity;
        $words = [];
        if ($identity->room_ids) {
            foreach ($identity->room_ids as $room_id) {
                $room_words = Words::getWords(true, $room_id);
                $room_words = ArrayHelper::getColumn($room_words, 'word');
                $words_str = implode(',', $room_words);
                $words[$room_id] = $words_str;
            }
        }
        return $this->render('index', [
            'words' => $words
        ]);
    }
}