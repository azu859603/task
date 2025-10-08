<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_gdt_token".
 *
 * @property int $id
 * @property int $advertiser_id
 * @property string $access_token
 * @property string $refresh_token
 * @property string $access_token_expires_in
 * @property string $refresh_token_expires_in
 */
class GdtToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_gdt_token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['access_token', 'refresh_token', 'access_token_expires_in', 'refresh_token_expires_in'], 'required'],
            [['advertiser_id'], 'integer'],
            [['access_token_expires_in', 'refresh_token_expires_in'], 'safe'],
            [['access_token', 'refresh_token'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'advertiser_id' => 'Advertiser ID',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'access_token_expires_in' => 'Access Token Expires In',
            'refresh_token_expires_in' => 'Refresh Token Expires In',
        ];
    }
}
