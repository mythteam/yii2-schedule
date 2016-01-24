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

use yii\base\Application;
use yii\base\InvalidParamException;
use yii\base\Object;

class CallbackEvent extends Event
{
    /**
     * The callback to call.
     *
     * @var string
     */
    protected $callback;
    /**
     * The parameters to pass to the method.
     *
     * @var array
     */
    protected $parameters;
    /**
     * @param string $callback
     * @param array  $parameters
     * @param array  $config
     */
    public function __construct($callback, array $parameters = [], $config = [])
    {
        $this->callback = $callback;
        $this->parameters = $parameters;
        Object::__construct($config);
        if (!is_string($this->callback) && !is_callable($this->callback)) {
            throw new InvalidParamException(
                'Invalid scheduled callback event. Must be string or callable.'
            );
        }
    }
    /**
     * Run the given event.
     *
     * @param Application $app
     *
     * @return mixed
     */
    public function run(Application $app)
    {
        $response = call_user_func_array($this->callback, array_merge($this->parameters, [$app]));
        $this->callAfterCallbacks($app);

        return $response;
    }
    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->_description)) {
            return $this->_description;
        }

        return is_string($this->callback) ? $this->callback : 'Closure';
    }
}
