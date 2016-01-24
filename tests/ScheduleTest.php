<?php

namespace mythteam\tests;

use mythteam\schedule\Schedule;
use Yii;

class ScheduleTest extends \PHPUnit_Framework_TestCase
{

    public function testExecCreatesNewCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';
        $escapeReal = '\\' === DIRECTORY_SEPARATOR ? '\\"' : '"';
        $schedule = new Schedule;
        $schedule->exec('path/to/command');
        $schedule->exec('path/to/command -f --foo="bar"');
        $events = $schedule->events;
        $this->assertEquals('path/to/command', $events[0]->command);
        $this->assertEquals('path/to/command -f --foo="bar"', $events[1]->command);
    }

    public function testCommandCreatesNewYiiCommand()
    {
        $schedule = new Schedule;
        $schedule->command('migrate');
        $schedule->command('migrate --migrationPath=@app');
        $events = $schedule->events;
        //$binary = PHP_BINARY . (defined('HHVM_VERSION') ? ' --php' : '');
        $binary = PHP_BINARY;
        $this->assertEquals($binary . ' yii migrate', $events[0]->command);
        $this->assertEquals($binary . ' yii migrate --migrationPath=@app', $events[1]->command);
    }

    public function testInstance()
    {
        $schedule = new Schedule;

        $schedule->exec('ls -a');
        $schedule->command('migrate');
        $schedule->call(function () {
            echo 'hello';
        });

        $this->assertEquals(3, count($schedule->events));

        return $schedule;
    }

    /**
     * @depends testInstance
     */
    public function testDueEvent($schedule)
    {
        $events = $schedule->dueEvents(Yii::$app);

        $this->assertEquals(3, count($events));
    }
}
