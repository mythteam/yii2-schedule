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

use yii\base\Component;
use yii\base\Application;

class Schedule extends Component
{
    /**
     * @var array All the events to scheduled
     */
    protected $_events = [];

    /**
     * Register a callback style schedule event.
     *
     * @param Course $callback
     * @param array  $parameters
     *
     * @return Event
     */
    public function call($callback, array $parameters = [])
    {
        $this->_events[] = $event = new CallbackEvent($callback, $parameters);

        return $event;
    }

    /**
     * Register a yii console command.
     *
     * @param string $command
     *
     * @return Event
     */
    public function command($command)
    {
        return $this->exec(PHP_BINARY . ' yii ' . $command);
    }

    /**
     * Register a new command event to schedule.
     *
     * @param string $command
     *
     * @return Event
     */
    public function exec($command)
    {
        $this->_events[] = $event = new Event($command);

        return $event;
    }

    /**
     * Get all scheduled events.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->_events;
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param Application $app
     *
     * @return array
     */
    public function dueEvents(Application $app)
    {
        return array_filter($this->_events, function (Event $event) use ($app) {
            return $event->isDue($app);
        });
    }
}
