<?php

namespace mythteam\tests;

use mythteam\schedule\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    protected $event;

    public function setUp()
    {
        $this->event = new Event('ls -a');
    }

    public function testGetDefaultExpression()
    {
        $this->assertEquals('* * * * * *', $this->event->expression);
    }

    /**
     * @dataProvider shortExpression
     */
    public function testShortExpressions($method, $expect)
    {
        call_user_func([$this->event, $method]);

        $this->assertEquals($expect, $this->event->expression);
    }

    /**
     * @dataProvider expressions
     */
    public function testExpressions()
    {
        $params = func_get_args();
        $method = array_shift($params);
        $expect = array_pop($params);

        call_user_func_array([$this->event, $method], $params);

        $this->assertEquals($expect, $this->event->expression);
    }

    public function testGetSummary()
    {
        $dest = '/dev/null';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $dest = 'NUL';
        }
        $this->assertEquals("ls -a >> {$dest} 2>&1 &", $this->event->getSummaryForDisplay());

        $this->event->description('test description');

        $this->assertEquals('test description', $this->event->getSummaryForDisplay());
    }

    public function testFileOutput()
    {
        //default
        $this->event->output('/home/schedule.log');

        $this->assertEquals('ls -a >> /home/schedule.log 2>&1 &', $this->event->buildCommand());
    }

    public function testAssignTimezone()
    {
        $this->event->timezone('PRC');

    }

    public function shortExpression()
    {
        return [
            ['hourly', '0 * * * * *'],
            ['daily', '0 0 * * * *'],
            ['twiceDaily', '0 1,13 * * * *'],
            ['weekdays', '* * * * 1-5 *'],
            ['mondays', '* * * * 1 *'],
            ['tuesdays', '* * * * 2 *'],
            ['wednesdays', '* * * * 3 *'],
            ['thursdays', '* * * * 4 *'],
            ['fridays', '* * * * 5 *'],
            ['saturdays', '* * * * 6 *'],
            ['sundays', '* * * * 0 *'],
            ['weekly', '0 0 * * 0 *'],
            ['monthly', '0 0 1 * * *'],
            ['yearly', '0 0 1 1 * *'],
            ['everyMinute', '* * * * * *'],
            ['everyFiveMinutes', '*/5 * * * * *'],
            ['everyTenMinutes', '*/10 * * * * *'],
            ['everyThirtyMinutes', '0,30 * * * * *'],
        ];
    }
    public function expressions()
    {
        return [
            ['cron', '* * *', '* * *'],
            ['at', '13:40', '40 13 * * * *'],
            ['dailyAt', '13:40', '40 13 * * * *'],

            ['days', 5, '* * * * 5 *'],

            ['weeklyOn', 3, '0 0 * * 3 *'],
            ['weeklyOn', 3, '12:42', '42 12 * * 3 *'],

            ['everyNMinutes', 2, '*/2 * * * * *'],
        ];
    }
}
