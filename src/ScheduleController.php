<?php

/*
 * This file is part of the mytheam/yii2-schedule.
 *
 * (c) mytheam <mytheam@hotmail.com>
 *
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file LICENSE.
 */

namespace mythteam\schedule;

use Yii;
use yii\di\Instance;
use yii\console\Controller;

/**
 * ~~~
 * * * * * * php /path/to/yii yii schedule 1>> /dev/null 2>&1
 * ~~.
 */
class ScheduleController extends Controller
{
    /**
     * @var string|Schedule
     */
    public $schedule = 'schedule';

    /**
     * @var string Schedule file path
     */
    public $scheduleFile;

    /**
     * {@inheritdoc}
     */
    public $defaultAction = 'run';

    /**
     * {@inheritdoc}
     */
    public function options($actionId)
    {
        return array_merge(
            parent::options($actionId),
            $actionId == 'run' ? ['scheduleFile'] : []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        if (Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, Schedule::className());
        } else {
            $this->schedule = Yii::createObject(Schedule::className());
        }
        parent::init();
    }

    public function actionRun()
    {
        $this->stdout(PHP_EOL);
        $this->importScheduleFile();

        /** @var Event[] $events */
        $events = $this->schedule->dueEvents(Yii::$app);

        foreach ($events as $event) {
            $this->stdout('Runing scheduled command ' . $event->getSummaryForDisplay() . PHP_EOL);
            $event->run(Yii::$app);
        }
        if (count($events) == 0) {
            $this->stdout('No scheduled commands are ready to run.' . PHP_EOL);
        }
    }

    /**
     * Import the schedule file and set the scheduled evetns.
     */
    protected function importScheduleFile()
    {
        if (null === $this->scheduleFile) {
            return;
        }

        $scheduleFile = Yii::getAlias($this->scheduleFile);

        if (file_exists($scheduleFile) == false) {
            $this->stderr("Can not load schedule file {$this->scheduleFile}" . PHP_EOL);

            return;
        }
        $schedule = $this->schedule;

        call_user_func(function () use ($schedule, $scheduleFile) {
            require $scheduleFile;
        });
    }
}
