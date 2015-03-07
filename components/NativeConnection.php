<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 07.03.15
 * Time: 14:14
 */

namespace insolita\redisman\components;


use insolita\redisman\Redisman;
use yii\base\Exception;
use yii\redis\Connection;

/**
 * Class NativeConnection  via phpredis php-extension
 * Early beta not recommended for use in production
 *
 * @package insolita\redisman\components
 */
class NativeConnection extends Connection{

    /**
     * @var \Redis $_socket
    **/
    private $_socket;

    /**
     * @var array List of available redis commands https://github.com/phpredis/phpredis
     */
    public $redisCommands = [
        'BGSAVE'=>'bgsave',
        'BRPOP'=>'brPop',
        'BRPOPLPUSH'=>'brpoplpush', // source destination timeout Pop a value from a list, push it to another list and return it; or block until one is available
        'CLIENT'=>'client', // ip:port Kill the connection of a client
        'CONFIG'=>'config',
        'DBSIZE'=>'dbSize',
        'DECR'=>'decr', // key Decrement the integer value of a key by one
        'DECRBY'=>'decrBy', // key decrement Decrement the integer value of a key by the given number
        'DEL'=>'del', // key [key ...] Delete a key
        'DISCARD'=>'discard', // Discard all commands issued after MULTI
        'DUMP'=>'dump', // key Return a serialized version of the value stored at the specified key.
        'ECHO'=>'echo', // message Echo the given string
        'EVAL'=>'evaluate', // script numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EVALSHA'=>'evalSha', // sha1 numkeys key [key ...] arg [arg ...] Execute a Lua script server side
        'EXEC'=>'exec', // Execute all commands issued after MULTI
        'EXISTS'=>'exists', // key Determine if a key exists
        'EXPIRE'=>'expire', // key seconds Set a key's time to live in seconds
        'EXPIREAT'=>'expireAt', // key timestamp Set the expiration for a key as a UNIX timestamp
        'FLUSHALL'=>'flushAll', // Remove all keys from all databases
        'FLUSHDB'=>'flushDB', // Remove all keys from the current database
        'GET'=>'get', // key Get the value of a key
        'GETBIT'=>'getBit', // key offset Returns the bit value at offset in the string value stored at key
        'GETRANGE'=>'getRange', // key start end Get a substring of the string stored at a key
        'GETSET'=>'getSet', // key value Set the string value of a key and return its old value
        'HDEL'=>'hDel', // key field [field ...] Delete one or more hash fields
        'HEXISTS'=>'hExists', // key field Determine if a hash field exists
        'HGET'=>'hGet', // key field Get the value of a hash field
        'HGETALL'=>'hGetAll', // key Get all the fields and values in a hash
        'HINCRBY'=>'hIncrBy', // key field increment Increment the integer value of a hash field by the given number
        'HINCRBYFLOAT'=>'hIncrByFloat', // key field increment Increment the float value of a hash field by the given amount
        'HKEYS'=>'hKeys', // key Get all the fields in a hash
        'HLEN'=>'hLen', // key Get the number of fields in a hash
        'HMGET'=>'hMGet', // key field [field ...] Get the values of all the given hash fields
        'HMSET'=>'hMset', // key field value [field value ...] Set multiple hash fields to multiple values
        'HSET'=>'hSet', // key field value Set the string value of a hash field
        'HSETNX'=>'hSetNx', // key field value Set the value of a hash field, only if the field does not exist
        'HVALS'=>'hVals', // key Get all the values in a hash
        'INCR'=>'incr', // key Increment the integer value of a key by one
        'INCRBY'=>'incrBy', // key increment Increment the integer value of a key by the given amount
        'INCRBYFLOAT'=>'incrByFloat', // key increment Increment the float value of a key by the given amount
        'INFO'=>'info', // [section] Get information and statistics about the server
        'KEYS'=>'keys', // pattern Find all keys matching the given pattern
        'LASTSAVE'=>'lastSave', // Get the UNIX time stamp of the last successful save to disk
        'LINDEX'=>'lIndex', // key index Get an element from a list by its index
        'LINSERT'=>'lInsert', // key BEFORE|AFTER pivot value Insert an element before or after another element in a list
        'LLEN'=>'lLen', // key Get the length of a list
        'LPOP'=>'lPop', // key Remove and get the first element in a list
        'LPUSH'=>'lPush', // key value [value ...] Prepend one or multiple values to a list
        'LPUSHX'=>'lPushx', // key value Prepend a value to a list, only if the list exists
        'LRANGE'=>'lRange', // key start stop Get a range of elements from a list
        'LREM'=>'lRem', // key count value Remove elements from a list
        'LSET'=>'lSet', // key index value Set the value of an element in a list by its index
        'LTRIM'=>'lTrim', // key start stop Trim a list to the specified range
        'MGET'=>'mget', // key [key ...] Get the values of all the given keys
        'MIGRATE'=>'migrate', // host port key destination-db timeout Atomically transfer a key from a Redis instance to another one.
        'MOVE'=>'move', // key db Move a key to another database
        'MSET'=>'mset', // key value [key value ...] Set multiple keys to multiple values
        'MSETNX'=>'msetnx', // key value [key value ...] Set multiple keys to multiple values, only if none of the keys exist
        'MULTI'=>'multi', // Mark the start of a transaction block
        'OBJECT'=>'object', // subcommand [arguments [arguments ...]] Inspect the internals of Redis objects
        'PERSIST'=>'persist', // key Remove the expiration from a key
        'PEXPIRE'=>'pExpire', // key milliseconds Set a key's time to live in milliseconds
        'PEXPIREAT'=>'pExpireAt', // key milliseconds-timestamp Set the expiration for a key as a UNIX timestamp specified in milliseconds
        'PING'=>'ping', // Ping the server
        'PSETEX'=>'psetex', // key milliseconds value Set the value and expiration in milliseconds of a key
        'PSUBSCRIBE'=>'psubscribe', // pattern [pattern ...] Listen for messages published to channels matching the given patterns
        'PTTL'=>'pttl', // key Get the time to live for a key in milliseconds
        'PUBLISH'=>'publish', // channel message Post a message to a channel
        'PUNSUBSCRIBE'=>'punsubscribe', // [pattern [pattern ...]] Stop listening for messages posted to channels matching the given patterns
        'RANDOMKEY'=>'randomKey', // Return a random key from the keyspace
        'RENAME'=>'rename', // key newkey Rename a key
        'RENAMENX'=>'renameNx', // key newkey Rename a key, only if the new key does not exist
        'RESTORE'=>'restore', // key ttl serialized-value Create a key using the provided serialized value, previously obtained using DUMP.
        'RPOP'=>'rPop', // key Remove and get the last element in a list
        'RPOPLPUSH'=>'rpoplpush', // source destination Remove the last element in a list, append it to another list and return it
        'RPUSH'=>'rPush', // key value [value ...] Append one or multiple values to a list
        'RPUSHX'=>'rPushx', // key value Append a value to a list, only if the list exists
        'SADD'=>'sAdd', // key member [member ...] Add one or more members to a set
        'SAVE'=>'save', // Synchronously save the dataset to disk
        'SCARD'=>'sCard', // key Get the number of members in a set
        'SCRIPT'=>'script',
        'SDIFF'=>'sDiff', // key [key ...] Subtract multiple sets
        'SDIFFSTORE'=>'sDiffStore', // destination key [key ...] Subtract multiple sets and store the resulting set in a key
        'SELECT'=>'select', // index Change the selected database for the current connection
        'SET'=>'set', // key value Set the string value of a key
        'SETBIT'=>'setBit', // key offset value Sets or clears the bit at offset in the string value stored at key
        'SETEX'=>'setEx', // key seconds value Set the value and expiration of a key
        'SETNX'=>'setNx', // key value Set the value of a key, only if the key does not exist
        'SETRANGE'=>'setRange', // key offset value Overwrite part of a string at key starting at the specified offset
        'SINTER'=>'sInter', // key [key ...] Intersect multiple sets
        'SINTERSTORE'=>'sInterStore', // destination key [key ...] Intersect multiple sets and store the resulting set in a key
        'SISMEMBER'=>'sIsMember', // key member Determine if a given value is a member of a set
        'SLAVEOF'=>'slaveOf', // host port Make the server a slave of another instance, or promote it as master
        'SLOWLOG'=>'slowlog', // subcommand [argument] Manages the Redis slow queries log
        'SMEMBERS'=>'sMembers', // key Get all the members in a set
        'SMOVE'=>'sMove', // source destination member Move a member from one set to another
        'SORT'=>'sort', // key [BY pattern] [LIMIT offset count] [GET pattern [GET pattern ...]] [ASC|DESC] [ALPHA] [STORE destination] Sort the elements in a list, set or sorted set
        'SPOP'=>'sPop', // key Remove and return a random member from a set
        'SRANDMEMBER'=>'sRandMember', // key [count] Get one or multiple random members from a set
        'SREM'=>'sRem', // key member [member ...] Remove one or more members from a set
        'STRLEN'=>'strlen', // key Get the length of the value stored in a key
        'SUBSCRIBE'=>'subscribe', // channel [channel ...] Listen for messages published to the given channels
        'SUNION'=>'sUnion', // key [key ...] Add multiple sets
        'SUNIONSTORE'=>'sInterStore', // destination key [key ...] Add multiple sets and store the resulting set in a key
        'TIME'=>'time', // Return the current server time
        'TTL'=>'ttl', // key Get the time to live for a key
        'TYPE'=>'type', // key Determine the type stored at key
        'UNSUBSCRIBE'=>'unsubscribe', // [channel [channel ...]] Stop listening for messages posted to the given channels
        'UNWATCH'=>'unwatch', // Forget about all watched keys
        'WATCH'=>'watch', // key [key ...] Watch the given keys to determine execution of the MULTI/EXEC block
        'ZADD'=>'zAdd', // key score member [score member ...] Add one or more members to a sorted set, or update its score if it already exists
        'ZCARD'=>'zCard', // key Get the number of members in a sorted set
        'ZCOUNT'=>'zCount', // key min max Count the members in a sorted set with scores within the given values
        'ZINCRBY'=>'zIncrBy', // key increment member Increment the score of a member in a sorted set
        'ZINTERSTORE'=>'zInterStore', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Intersect multiple sorted sets and store the resulting sorted set in a new key
        'ZRANGE'=>'zRange', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index
        'ZRANGEBYSCORE'=>'zRangeByScore', // key min max [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score
        'ZRANK'=>'zRank', // key member Determine the index of a member in a sorted set
        'ZREM'=>'zRem', // key member [member ...] Remove one or more members from a sorted set
        'ZREMRANGEBYRANK'=>'zRemRangeByRank', // key start stop Remove all members in a sorted set within the given indexes
        'ZREMRANGEBYSCORE'=>'zRemRangeByScore', // key min max Remove all members in a sorted set within the given scores
        'ZREVRANGE'=>'zRevRange', // key start stop [WITHSCORES] Return a range of members in a sorted set, by index, with scores ordered from high to low
        'ZREVRANGEBYSCORE'=>'zRevRangeByScore', // key max min [WITHSCORES] [LIMIT offset count] Return a range of members in a sorted set, by score, with scores ordered from high to low
        'ZREVRANK'=>'zRevRank', // key member Determine the index of a member in a sorted set, with scores ordered from high to low
        'ZSCORE'=>'zScore', // key member Get the score associated with the given member in a sorted set
        'ZUNIONSTORE'=>'zUnionStore', // destination numkeys key [key ...] [WEIGHTS weight [weight ...]] [AGGREGATE SUM|MIN|MAX] Add multiple sorted sets and store the resulting sorted set in a new key
    ];

    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        $this->close();

        return array_keys(get_object_vars($this));
    }
    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @return \Redis
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->_socket !== null) {
            return;
        }
        $connection = ($this->unixSocket ?: $this->hostname . ':' . $this->port) . ', database=' . $this->database;
        \Yii::trace('Opening redis DB connection: ' . $connection, __METHOD__);
        $this->_socket=new \Redis();
        if($this->unixSocket){
            $this->_socket->connect($this->unixSocket, $this->port, $this->dataTimeout);
        }else{
            $this->_socket->connect($this->hostname, $this->port, $this->dataTimeout);
        }
        if (isset($this->password)) {
            if ($this->_socket->auth($this->password) === false) {
                throw new Exception('Redis authentication failed!');
            }
        }
        $this->_socket->select($this->database);
        return $this->_socket;

    }

    /**
     * @param string $name
     * @param array  $params
     *
     * @return mixed
     * @throws Exception
     */
    public function executeCommand($name, $params = [])
    {
        $this->open();
        if(!isset($this->redisCommands[$name])){
            throw new Exception(Redisman::t('redisman','Method '.$name.' not supported by '.get_class($this).' yet'));
        }
        $name=$this->redisCommands[$name];
        return call_user_func_array(array($this->_socket,$name), $params);
    }

    /**
     * @param string $name
     * @param array  $arguments
     * @return array|string|int|bool
     */
    public function __call($name, $arguments)
    {
            try{
                return call_user_func_array(array($this->_socket,$name), $arguments);
            }
            catch (Exception $e) {
                $this->handle_exception($e,$name,$arguments);
            }
    }

    /**
     * @param $e
     * @param $name
     * @param $args
     */
    private function handle_exception($e,$name,$args)
    {
        $err=$e->getMessage();
        $msg="Caught exception: ".$err."\tcall ".$name."\targs ".implode(" ",$args)."\n";
        \Yii::error($msg);
    }

    /**
     *
     */
    public function close(){
        if($this->_socket){
            $this->_socket->close();
            $this->_socket=null;
        }
    }

    /**
     * Initializes the DB connection.
     * This method is invoked right after the DB connection is established.
     * The default implementation triggers an [[EVENT_AFTER_OPEN]] event.
     */
    protected function initConnection()
    {
        $this->trigger(self::EVENT_AFTER_OPEN);
    }
} 