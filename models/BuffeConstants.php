<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "buffe_constants".
 *
 * @property string $name
 * @property string $value
 */
class BuffeConstants extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'buffe_constants';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['name'], 'string', 'max' => 30],
            [['value'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Name',
            'value' => 'Value',
        ];
    }
}
