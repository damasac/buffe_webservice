<?php

namespace app\controllers;
use yii\rest\ActiveController;

class BuffeConstantsController extends ActiveController
{
    public $modelClass = 'app\models\BuffeConstants';
    
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => [],
            'create' => [],
            'update' => [],
            'delete' => [],
        ];
    }     
    public function actions()
    {
        $actions = parent::actions();

        // customize the data provider preparation with the "prepareDataProvider()" method
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }    
    
    public function prepareDataProvider()
    {   
        $token = \Yii::$app->request->get('token');
        $user=\app\models\User::findIdentityByAccessToken($token);
        //\yii\helpers\VarDumper::dump($user);
        $profile=\app\models\UserProfile::findOne($user->id);
        $sitecode=  $profile->sitecode;
        $constants = \app\models\BuffeConstants::find()->all();
        
        $khet = \Yii::$app->db->createCommand("SELECT zone_code,name from all_hospital_thai where hcode='{$sitecode}'")->queryOne();
        
        if (count($constants)>0) foreach ($constants as $key => $constant) {
            if ($constant->id == "_SITECODE_") $constant->value=$sitecode;
            if ($constant->id == "_KHET_") {
                $constant->value=$khet['zone_code'];
            }
            if ($constant->id == "_HOSPITAL_") $constant->value=$khet['name'];
            if ($constant->id == "_USERFULLNAME_") $constant->value=$profile->firstname . " " . $profile->lastname;
        }
        return $constants;
    }        
    
    public function actionQuery()
    {
        $sql = \Yii::$app->request->get('sql');
        $model=\Yii::$app->db->createCommand($sql)->queryAll();
        return $model;
    }
    public function actionHisType() 
    {
        $sql = "SELECT * from buffe_his where status=1";
        $model=\Yii::$app->db->createCommand($sql)->queryAll();
        return $model;        
    }
}
