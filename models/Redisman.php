<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 17.08.14
 * Time: 11:22
 */
namespace insolita\redisman\models;
use Redis;
use yii\base\InvalidCallException;
use yii\base\Model;

class Redisman extends Model{

    /**
     * Returns list of redis object types, or string name of current type
     * @param (string|null)
     * @return string
     */
    public static function getTypeList($type=null){
        $types=array(
            Redis::REDIS_STRING=>\Yii::t('redisman','строка'),
            Redis::REDIS_SET=>\Yii::t('redisman','Набор'),
            Redis::REDIS_LIST=>\Yii::t('redisman','Список'),
            Redis::REDIS_ZSET=>\Yii::t('redisman','Множество'),
            Redis::REDIS_HASH=>\Yii::t('redisman','Хэш'));
        return isset($types[$type])?$types[$type]:$types;
    }
    /**
     * Returns the database connection used by this  class.
     * By default, the "redis" application component is used as the database connection.
     * You may override this method if you want to use a different database connection.
     * @return \yii\redis\Connection the database connection used by this  class.
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
        $keys=$db->executeCommand('KEYS',[$group]);
        if(!is_array($keys)) $keys=[];
        return $keys;
    }


    /**Возвращает список групп (ключей разбиваемых по разделительному символу)
     * @param string $startgroup начальная группа
     * @param int $depth глубина разбиения - 0 - первый уровень
     * @return array
     */
    public static function getGrouplist($startgroup='*',$depth=0){
       if(($gl=\Yii::$app->cache->get($startgroup.'_redisgrouplist'))==false){
           $groupped=['@'=>\Yii::t('redisman','Без группы')];
           $keys=static::getAllkeys($startgroup);
           foreach($keys as $key){
               $keyparts=explode(\Yii::$app->getModule('redisman')->groupDelimiter,$key);
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
           \Yii::$app->cache->set($startgroup.'_redisgrouplist',$groupped,\Yii::$app->getModule('redisman')->grouplistCacheDuration);
           return $groupped;
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
        $db->executeCommand('EXPIRE',$key, $ttl);
    }


    /**
     * @param $key
     * @return array|bool|null|string
     */
    public static  function getExpire($key){
        //$ttl=time()+$ttl;
        $db=static::getDb();
        return $db->executeCommand('TTL',$key);
    }


    /**
     * Universal redis data getter
     * @param $key
     * @return array|bool|null|string
     * @throws \yii\base\InvalidCallException
     */
    public static function getData($key){
        $db=static::getDb();
        $type=self::getType($key,true);
        if($type==Redis::REDIS_STRING){
            return $db->executeCommand('GET',[$key]);
        }elseif($type==Redis::REDIS_HASH){
            return $db->executeCommand('HGETALL',[$key]);
        }elseif($type==Redis::REDIS_ZSET){
            return $db->executeCommand('ZREVRANGE',[$key,0,-1]);
        }elseif($type==Redis::REDIS_SET){
            return $db->executeCommand('SMEMBERS',[$key]);
        }elseif($type==Redis::REDIS_LIST){
            return $db->executeCommand('LRANGE',[$key,0,-1]);
        }else{
            throw new InvalidCallException('Unknown type of data');
        }
    }


    /**
     * Universal Redis data getter
     * @param $type
     * @param $key
     * @param $val
     * @throws \yii\base\InvalidCallException
     */
    public static function add($type,$key,$val){
        $db=static::getDb();
        $ins=is_array($val)?array_unshift($val,$key):[$key,$val];
        if($type==Redis::REDIS_STRING){
             $db->executeCommand('SET',$ins);
        }elseif($type==Redis::REDIS_HASH){
             $db->executeCommand('HMSET',$ins);
        }elseif($type==Redis::REDIS_ZSET){
             $db->executeCommand('ZADD',$ins);
        }elseif($type==Redis::REDIS_SET){
             $db->executeCommand('SADD',$ins);
        }elseif($type==Redis::REDIS_LIST){
             $db->executeCommand('RPUSH',$ins);
        }else{
            throw new InvalidCallException('Unknown type of data');
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