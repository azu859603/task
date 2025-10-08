<?php

namespace common\models\tea;

use common\enums\CacheEnum;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "rf_words".
 *
 * @property string $id
 * @property string $word 敏感词
 * @property string $room_id 房间id
 */
class Words extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rf_words';
    }

    /**
     * 获取敏感词
     * @param $noCache
     * @param $id
     * @return array|mixed|ActiveRecord[]
     */
    public static function getWords($noCache = false, $id = '')
    {

        $room_id = $id;
        $cacheKey = CacheEnum::COMMON_WORDS . $room_id;
        if (!($info = Yii::$app->cache->get($cacheKey)) || $noCache == true) {
            $info = self::find()->where(['room_id' => $id])->asArray()->all();
            // 设置缓存
            Yii::$app->cache->set($cacheKey, $info);
        }
        return $info;
    }

    /**
     *  匹配敏感词
     * @param $content
     * @return bool
     */
    public static function matchWords($id,$content)
    {

        $words = self::getWords(false,$id);
        foreach ($words as $word){
            if (strpos($content, $word['word']) !== false) {
                return false;
            }
        };
        return true;
    }
}
