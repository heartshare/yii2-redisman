<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 13:05
 */
namespace insolita\redisman\models;

class RedisItem extends \yii\base\Object{
   public $key;
   public $type;
   public $value;

   public function init(){
       if($this->type!=Redis::REDIS_STRING){
           $objvalue=[];
           foreach($this->value as $i=>$val){
               $objvalue[]=new RedisSubitem(['key'=>$i,'type'=>$this->type,'value'=>$val]);
           }
           $this->value=$objvalue;
       }
   }
} 