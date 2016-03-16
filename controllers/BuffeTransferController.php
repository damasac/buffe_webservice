<?php

namespace app\controllers;

use yii\rest\ActiveController;
use app\models\User;
use app\models\UserProfile;
use app\models\BuffeTransfer;

class BuffeTransferController extends ActiveController
{
    public $modelClass = 'app\models\BuffeConfig';
    
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'enable' => ['GET'],
            'view' => ['GET'],
            'create' => [],
            'update' => [],
            'delete' => [],
            'sync' => ['POST'],
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

        $user=User::findIdentityByAccessToken($token);
        if ($user->id == "") return null;
        $sitecode=  UserProfile::findOne($user->id)->sitecode;
        
        if ($sitecode == "") return null;

        return $command = BuffeTransfer::find()->where('sitecode=:sitecode and status is null',[':sitecode'=>$sitecode])->all();
        
    }
    
    public function actionEnable() {

        if ((\Yii::$app->request->get('id')=="")||(\Yii::$app->request->get('id') == null))
        {
            $id=0;
        }
        else
        {
            $id = \Yii::$app->request->get('id');
        }
            try {
                $command = BuffeTransfer::findOne($id);
                if ($command) 
                {
                    $command->status=0;
                    $command->save();
                    return $command;
                    return $result['status']="OK";
                }else{
                    $result['status']="Error";
                    $result['Error']="Enable error: ID is not specify.";

                    return $result;                      
                }
            }
            catch (\yii\db\Exception $e)
            {
                $result['status']="Error";
                $result['Error']=$e->getMessage();

                return $result;                  
            }

        //$result['status']="Error";
        //$result['Error']=$command->getErrors();

        //return $result;        
    }
    
    public function actionSync() {
        $token = \Yii::$app->request->get('token');

        $user=User::findIdentityByAccessToken($token);
        $hospcode=  UserProfile::findOne($user->id)->sitecode;
        
        $id = \Yii::$app->request->post('id'); 
        $sitecode = \Yii::$app->request->post('sitecode'); 
        $qleft = \Yii::$app->request->post('qleft');
        if ($qleft=="1") $qleft=0;
        $datadb=\Yii::$app->params['datadb'];
        
        $command = BuffeTransfer::find()->where("id=:id and sitecode=:sitecode",[':id'=>$id,':sitecode'=>$sitecode])->one();

        if (!$command) {
            $command = new BuffeTransfer();
            foreach (\Yii::$app->request->post() as $key => $value) {
                $command->$key=$value;
            }
            $command->save();
        }else{
            foreach (\Yii::$app->request->post() as $key => $value) {
                $command->$key=$value;
            }
            $command->save();            
        }
        $command = BuffeTransfer::find()->where("id=:id and sitecode=:sitecode",[':id'=>$id,':sitecode'=>$sitecode])->one();

        if (($command->result != "")&&($command->table != ""))
        {
            $table=$command->table;
            $results = json_decode($command->result);
            if (\count($results)>0) 
            {                
                if (($command->controller == "replace")||($command->controller == "")) {
                    foreach ($results as $recno => $result) {
                        $setsql="";
                        foreach($result as $field => $data) {
                            $data = addslashes($data);
                            $setsql .= ",`{$field}`='$data'";
                        }
                        if (strpos($setsql,'`sitecode`')===false) 
                        {
                            $setsql .= ",`sitecode`='$sitecode'";
                        }
                        $setsql=  substr($setsql, 1);
                        $sql="REPLACE INTO `{$datadb}`.`{$command->table}` SET  {$setsql}";
                        \Yii::$app->db->createCommand($sql)->query();
                        $sql="SELECT 1 from `buffe_table_server` WHERE sitecode='$sitecode' and `table`='{$table}'";
                        $tbcount = \Yii::$app->db->createCommand($sql)->query()->count();
                        if ($tbcount == 0) {
                            $sql="INSERT INTO `buffe_table_server` (sitecode,`table`) VALUES('{$sitecode}','{$table}')";
                            \Yii::$app->db->createCommand($sql)->query();
                        }
                        $sql = "UPDATE buffe_table_server set qleft='{$qleft}' WHERE sitecode='$sitecode' and `table`='{$table}'";
                        \Yii::$app->db->createCommand($sql)->query();
                    }
                }
                if ($command->controller == "delete") {
                    foreach ($results as $recno => $result) {
                        $key=\Yii::$app->db->createCommand("SHOW KEYS FROM `{$datadb}`.`{$command->table}` WHERE Key_name = 'PRIMARY'")->query();
                        foreach ($key as $k => $pk) {
                            $pk1=$pk['Column_name'];
                            $pkv=$result->$pk1;
                            $where .= "AND `$pk1`='$pkv'";
                        }
                        $sql="DELETE FROM `{$datadb}`.`{$command->table}` WHERE 1 $where";
                        \Yii::$app->db->createCommand($sql)->query();
                        $sql="SELECT 1 from `buffe_table_server` WHERE sitecode='$sitecode' and `table`='{$table}'";
                        $tbcount = \Yii::$app->db->createCommand($sql)->query()->count();
                        if ($tbcount == 0) {
                            $sql="INSERT INTO `buffe_table_server` (sitecode,`table`) VALUES('{$sitecode}','{$table}')";
                            \Yii::$app->db->createCommand($sql)->query();
                        }                        
                        $sql="UPDATE buffe_table_server set qleft='{$qleft}' WHERE sitecode='$sitecode' and `table`='{$table}'";
                        \Yii::$app->db->createCommand($sql)->query();                        
                    }
                }
                if ($command->controller == "update") {
                    foreach ($results as $recno => $result) {
                        foreach($result as $field => $data) {
                            $data = addslashes($data);
                            $setsql .= ",`{$field}`='$data'";
                        }                       
                        $setsql=  substr($setsql, 1);
                        $key=\Yii::$app->db->createCommand("SHOW KEYS FROM `{$datadb}`.`{$command->table}` WHERE Key_name = 'PRIMARY'")->query();
                        foreach ($key as $k => $pk) {
                            $pk1=$pk['Column_name'];
                            $pkv=$result->$pk1;
                            $where .= "AND `$pk1`='$pkv'";
                        }
                        $sql="UPDATE `{$datadb}`.`{$command->table}` SET $setsql WHERE 1 $where";
                        \Yii::$app->db->createCommand($sql)->query();
                        $sql="SELECT 1 from `buffe_table_server` WHERE sitecode='$sitecode' and `table`='{$table}'";
                        $tbcount = \Yii::$app->db->createCommand($sql)->query()->count();
                        if ($tbcount == 0) {
                            $sql="INSERT INTO `buffe_table_server` (sitecode,`table`) VALUES('{$sitecode}','{$table}')";
                            \Yii::$app->db->createCommand($sql)->query();
                        }
                        $sql="UPDATE buffe_table_server set qleft='{$qleft}' WHERE sitecode='$sitecode' and `table`='{$table}'";
                        \Yii::$app->db->createCommand($sql)->query();                        
                    }                    
                }
            }

        }

        $cpu = \Yii::$app->request->get('cpu');
        $ram = \Yii::$app->request->get('ram');        
        $sql="Update buffe_config set cpu='{$cpu}',ram='{$ram}' where id='{$sitecode}'";
        \Yii::$app->db->createCommand($sql)->query();
        
        //update status
        $command->dupdate=date('Y-d-m h:i:s');
        $command->status='4';

        if ($command->save())
        {
            $resultx['result']="OK";
            $deltransfer=  \Yii::$app->db->createCommand()->delete("buffe_transfer", ['id'=>$id,'sitecode'=>$sitecode])->query();
        }
        else
        {
            $resultx['result']="Error";
            $resultx['Error']=$command->getErrors();
        }
        
        return $resultx;
    }
}
