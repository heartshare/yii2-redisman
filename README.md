RedisManager Module
======================
Manage Redis Databases (beta)
Demo http://yii2redis-insolita1.c9.io/
Source -app https://github.com/Insolita/yii2-redisman-app

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist insolita/yii2-redisman "*"
```

or add

```
"insolita/yii2-redisman": "*"
```

to the require section of your `composer.json` file.


Usage
-----

```php
       'modules' => [
           'redisman' => [
               'class'=>'\insolita\redisman\Redisman',
               'connections'=>[
                   'local'=>[
                       'class' => 'yii\redis\Connection',
                       'hostname' => 'localhost',
                       'port' => 6379,
                       'database' => 0,
                   ],
                   'remote1'=>[
                       'class' => 'yii\redis\Connection',
                       'hostname' => '123.456.222.111',
                       'port' => 6379,
                       'database' => 0,
                       //  'unixSocket'=>'/tmp/redis.sock'
                   ],
                   'workremote'=>[
                      'class' => 'yii\redis\Connection',
                       'hostname' => '321.654.221.111',
                      'port' => 6379,
                      'database' => 0,
                    ],
               ],
               'defRedis'=>'local',
               'searchMethod'=>'SCAN',
               'greedySearch'=>false,
               'on beforeFlushDB'=>function($event){
                               if($event->data['db']==3){
                                    $event->isValid=false;
                               }else{
                                   $event->isValid=true;
                               }
                           },
           ],

       ],
```