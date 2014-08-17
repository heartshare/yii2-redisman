<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 11:22
 */

class Redis extends \yii\base\Model{
    /**
     * Returns list of redis object types, or string name of current type
     * @return string
     */
    public static function getTypeList($type=null){
        $types=[Redis::REDIS_STRING=>Yii::t('redisman','строка'),
            Redis::REDIS_SET=>Yii::t('redisman','Набор'),
            Redis::REDIS_LIST=>Yii::t('redisman','Список'),
            Redis::REDIS_ZSET=>Yii::t('redisman','Множество'),
            Redis::REDIS_HASH=>Yii::t('redisman','Хэш')];
        return isset($types[$type])?$types[$type]:$types;
    }
    /**
     * Returns the database connection used by this  class.
     * By default, the "redis" application component is used as the database connection.
     * You may override this method if you want to use a different database connection.
     * @return Connection the database connection used by this  class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('redis');
    }

    /**
     * Определяет тип записи в базе
     * @param string $key  Ключ
     * @param bool $native Вернуть redis-тип или нормализованное строковое название
     * @return string
     */
    public static function getType($key, $native=false){
        $db=static::getDb();
        $type= $db->executeCommand('TYPE',$key);
        return ($native)?$type:self::getTypeList($type);
    }

    /**
     * Возвращает список всех ключей, или ключей заданной группы
     * @param string $group
     * @return array
     */
    public static function getAllkeys($group='*'){
        $db=static::getDb();
        $keys=$db->executeCommand('KEYS',$group);
        if(is_array($keys)) $keys=[];
        return $keys;
    }
    /**
     * Возвращает список групп (ключей разбиваемых по разделительному символу)
     * @param string $startgroup - начальная группа
     * @param string $depth - глубина разбиения - 0 - первый уровень;
    */
    public static function getGrouplist($startgroup='*',$depth=0){
       $db=static::getDb();
       if(($gl=Yii::$app->cache->get($startgroup.'_redisgrouplist'))==false){
           $groupped=['@'=>Yii::t('redisman','Без группы')];
           $keys=static::getAllkeys($startgroup);
           foreach($keys as $key){
               $keyparts=explode(Yii::$app->getModule('redisman')->groupDelimiter,$key);
               $kp_count=count($keyparts);
               if($kp_count>1){
                   $stopper=($depth>=$kp_count)?$kp_count-1:$depth;
                   for($i=0;$i<=$stopper;$i++){
                       $group='';
                       for($c=0;$c<=$i;$c++){
                           $group.=$keyparts[$c];
                        }
                       $groupped[$group]=$group;
                   }
               }
           }
       }else{
           return $gl;
       }
    }

    /**
     * @param string $key
     * @param $ttl - live time in seconds
     */
    public static  function setExpire($key,$ttl){
        //$ttl=time()+$ttl;
        $db=static::getDb();
        $db->executeCommand('EXPIRE',$key);
    }
    /**
     * @param string $key
     * @return $ttl - live time in seconds
     */
    public static  function getExpire($key){
        //$ttl=time()+$ttl;
        $db=static::getDb();
        return $db->executeCommand('TTL',$key);
    }
    /**
     * Universal data getter from db
     * @param string $key
     * @throw \yii\base\InvalidCallException
     */
    public static function getData($key){
        $db=static::getDb();
        $type=self::getType($key,true);
        if($type==Redis::REDIS_STRING){
            return $db->executeCommand('GET',$key);
        }elseif($type==Redis::REDIS_HASH){
            return $db->executeCommand('HGETALL',$key);
        }elseif($type==Redis::REDIS_ZSET){
            return $db->executeCommand('ZREVRANGE',$key,0,-1);
        }elseif($type==Redis::REDIS_SET){
            return $db->executeCommand('SMEMBERS',$key);
        }elseif($type==Redis::REDIS_LIST){
            return $db->executeCommand('LRANGE',$key,0,-1);
        }else{
            throw new \yii\base\InvalidCallException('Unknown type of data');
        }
    }
    /**
     * Universal data setter to db
     * @param string $key
     * @param Redis $type
     * @param string|array|integer  $val
     * @throw \yii\base\InvalidCallException
     */
    public static function add($type,$key,$val){
        $db=static::getDb();
        if($type==Redis::REDIS_STRING){
             $db->executeCommand('SET',$key,$val);
        }elseif($type==Redis::REDIS_HASH){
             $db->executeCommand('HMSET',$key,$val);
        }elseif($type==Redis::REDIS_ZSET){
             $db->executeCommand('ZADD',$key,$val);
        }elseif($type==Redis::REDIS_SET){
             $db->executeCommand('SADD',$key,$val);
        }elseif($type==Redis::REDIS_LIST){
             $db->executeCommand('RPUSH',$key,$val);
        }else{
            throw new \yii\base\InvalidCallException('Unknown type of data');
        }
    }

    /**
     * Key renamer
     * @param string $key
     * @param string $newkey
     */
    public static function rename($key,$newkey){
        $db=static::getDb();
        $db->executeCommand('RENAME',$key,$newkey);
    }

} 