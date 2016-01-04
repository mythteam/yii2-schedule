<?php

namespace mythteam\tests;

use mythteam\schedule\CallbackEvent;
use Yii;

class CallbackEventTest extends EventTest
{
    public function setUp()
    {
        $this->event = new CallbackEvent(function () {
            return 'string';
        });
    }

    public function testRun()
    {
        $ret = $this->event->run(Yii::$app);

        $this->assertEquals('string', $ret);
    }

    /**
     * @expectedException \yii\base\InvalidParamException
     */
    public function testInvalidInstance()
    {
        new CallbackEvent(1);
    }

    public function testGetSummary()
    {
        $this->assertEquals('Closure', $this->event->getSummaryForDisplay());

        $this->event->description('test');
        $this->assertEquals('test', $this->event->getSummaryForDisplay());

        //test for string callback

        $event = new CallbackEvent('test');
        $this->assertEquals('test', $this->event->getSummaryForDisplay());
    }

    //true test of output?
    public function testFileOutput()
    {
        $this->assertTrue(true);
    }
}
