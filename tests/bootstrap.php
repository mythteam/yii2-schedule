<?php
define('YII_DEBUG', true);
define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

new \yii\console\Application([
    'id' => 'unit',
    'name' => 'unit',
    'basePath' => __DIR__,
    'controllerMap' => [
        'schedule' => [
            'class' => 'common\business\schedule\ScheduleController',
            'scheduleFile' => '@app/fixture/schedule.php',
        ],
    ],
]);
