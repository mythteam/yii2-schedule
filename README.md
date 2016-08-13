yii2-schedule
------------

This extension can schedule your works with [Yii framework 2.0](http://www.yiiframework.com/).

[![Build Status](https://img.shields.io/travis/mythteam/yii2-schedule.svg?style=flat-square)](http://travis-ci.org/mythteam/yii2-schedule)
[![version](https://img.shields.io/packagist/v/mythteam/yii2-schedule.svg?style=flat-square)](https://packagist.org/packages/mythteam/yii2-schedule)
[![Download](https://img.shields.io/packagist/dt/mythteam/yii2-schedule.svg?style=flat-square)](https://packagist.org/packages/mythteam/yii2-schedule)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/mythteam/yii2-schedule.svg?style=flat-square)](https://scrutinizer-ci.com/g/mythteam/yii2-schedule)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mythteam/yii2-schedule.svg?style=flat-square)](https://scrutinizer-ci.com/g/mythteam/yii2-schedule)
[![Contact](https://img.shields.io/badge/weibo-@chunqiang-blue.svg?style=flat-square)](http://weibo.com/chunqiang)

[README](README.md) | [中文文档](README_zh.md)

## TOC

* [Install](#installation)
* [Getting Start](#getting-start)
## Installation

The preferred way to install this extension is througth [composer](http://getcomposer.org/download/)

Either run 

```
php composer.phar require --prefer-dist mythteam/yii2-schedule
```

or add

```
"mythteam/yii2-shedule: "~1.0.0"
```

to the require section of your composer.json.

## Getting Start

Make controllerMap in your console configration file:

> There we used [yii2-app-advanced](https://github.com/yiisoft/yii2-app-advanced/) for example.

In `console/config/main.php`

```
'controllerMap' => [
	'schedule' => [
		'class' => 'mythteam\schedule\ScheduleController',
		'scheduleFile' => '@app/config/schedule.php',
	]
]
```

Create the `schedule.php` file in config directory. 

> If you use the phpstorm as your IDE, you can write `/** @var \mythteam\schedule\Schedule $schedule */` in the first line of the `schedule.php`, this can help you suggestion and automation the internal functions.


Make schedule in crontable:

```
*/1 * * * php /path/to/yii shedule > /dev/null
```

## License

[![MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
