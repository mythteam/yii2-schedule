<?php

namespace mythteam\tests;

use Yii;
use mythteam\schedule\Schedule;

class ScheduleTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $schedule = new Schedule;

        $schedule->exec('ls -a');
        $schedule->command('migrate');
        $schedule->call(function() {
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
