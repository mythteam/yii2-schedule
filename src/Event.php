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

use Cron\CronExpression;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Process\Process;
use yii\base\Application;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\mail\MailerInterface;

class Event extends Component
{
    const EVENT_BEFORE_RUN = 'beforeRun';
    const EVENT_AFTER_RUN = 'afterRun';

    /**
     * @var string The command to be executed.
     */
    public $command;

    /**
     * @var string The crontab expression of the event's frequency.
     */
    protected $_expression = '* * * * * *';

    /**
     * @var \DateTimeZone|string The tiemzone the date should be evaluated.
     */
    protected $_timezone;

    /**
     * @var string The user should run the command.
     */
    protected $_user;

    /**
     * @var \Closure The filter callback.
     */
    protected $_filter;

    /**
     * @var \Closure The reject callback.
     */
    protected $_reject;

    /**
     * @var string The location that output should be send to.
     */
    protected $_output;

    /**
     * @var array The array of callbacks of to be run after event is finished.
     */
    protected $_afterCallbacks = [];

    /**
     * @var string The human readable description of the event.
     */
    protected $_description;

    /**
     * Create a new event instance.
     *
     * @param string $command
     * @param array $config
     */
    public function __construct($command, $config = [])
    {
        $this->command = $command;
        $this->_output = $this->getDefaultOutput();
        parent::__construct($config);
    }

    /**
     * Get the expression.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->_expression;
    }

    /**
     * Set the timezone.
     *
     * @param \DateTimeZone|string $timezone
     *
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->_timezone = $timezone;

        return $this;
    }

    /**
     * Set the execute user.
     *
     * @param string $user
     *
     * @return $this
     */
    public function user($user)
    {
        $this->_user = $user;

        return $this;
    }

    /**
     * Set the command output message export location.
     *
     * @param string $path
     *
     * @return $this
     */
    public function output($path)
    {
        $this->_output = $path;

        return $this;
    }

    /**
     * Set the description.
     *
     * @param string $description
     *
     * @return $this
     */
    public function description($description)
    {
        $this->_description = $description;

        return $this;
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

        return $this->buildCommand();
    }

    /**
     * Register the callback function to execute after schedule.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function then(\Closure $callback)
    {
        $this->_afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to the ping a given URL after the job runs.
     *
     * @param string $url
     *
     * @return $this
     */
    public function thenPing($url)
    {
        return $this->then(function () use ($url) {
            (new HttpClient())->get($url);
        });
    }

    /**
     * Register a callback  to further filter the  schedule.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function when(\Closure $callback)
    {
        $this->_filter = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function skip(\Closure $callback)
    {
        $this->_reject = $callback;

        return $this;
    }

    /**
     * Register the send email logic.
     *
     * @param array $address
     *
     * @return $this
     */
    public function emailOutput($address)
    {
        if (is_null($this->_output)
            || $this->_output == $this->getDefaultOutput()
        ) {
            throw new InvalidCallException('Must direct output to file in order to email results.');
        }
        $address = is_array($address) ? $address : func_get_args();

        return $this->then(function (Application $app) use ($address) {
            $thsi->sendEmail($app->mailer, $address);
        });
    }

    /**
     * Run the event.
     *
     * @param \yii\base\Application $app
     */
    public function run(Application $app)
    {
        $this->trigger(self::EVENT_BEFORE_RUN);
        if (count($this->_afterCallbacks) > 0) {
            $this->runCommandInForeground($app);
        } else {
            $this->runCommandInBackground($app);
        }
        $this->trigger(self::EVENT_AFTER_RUN);
    }

    /**
     * @param \yii\base\Application $app
     */
    protected function runCommandInForeground(Application $app)
    {
        (new Process(
            trim($this->buildCommand(), '& '), dirname($app->request->getScriptFile()), null, null, null
        ))->run();
        $this->callAfterCallbacks($app);
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * @param Application $app
     */
    protected function callAfterCallbacks(Application $app)
    {
        foreach ($this->_afterCallbacks as $callback) {
            call_user_func($callback, $app);
        }
    }

    /**
     * Run the command in the background using exec.
     *
     * @param Application $app
     */
    protected function runCommandInBackground(Application $app)
    {
        chdir(dirname($app->request->getScriptFile()));
        exec($this->buildCommand());
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @param Application $app
     *
     * @return bool
     */
    public function isDue(Application $app)
    {
        return $this->expressionPasses() && $this->filtersPass($app);
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        $date = new \DateTime('now');
        if ($this->_timezone) {
            $date->setTimezone($this->_timezone);
        }

        return CronExpression::factory($this->_expression)->isDue($date);
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param Application $app
     *
     * @return bool
     */
    protected function filtersPass(Application $app)
    {
        if (($this->_filter && ($this->_filter))
            || $this->_reject && call_user_func($this->_reject, $app)
        ) {
            return false;
        }

        return true;
    }

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param string $expression
     *
     * @return $this
     */
    public function cron($expression)
    {
        $this->_expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->cron('0 * * * * *');
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->cron('0 0 * * * *');
    }

    /**
     * Schedule the command at a given time.
     *
     * @param string $time
     *
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param string $time
     *
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int)$segments[0])
            ->spliceIntoPosition(1, count($segments) == 2 ? (int)$segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @return $this
     */
    public function twiceDaily()
    {
        return $this->cron('0 1,13 * * * *');
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param array|int $days
     *
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->cron('0 0 * * 0 *');
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param int $day
     * @param string $time
     *
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->cron('0 0 1 * * *');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->cron('0 0 1 1 * *');
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->cron('* * * * * *');
    }

    /**
     * Schedule the event to run every N minutes.
     *
     * @param int|string $minutes
     *
     * @return $this
     */
    public function everyNMinutes($minutes)
    {
        return $this->cron('*/' . $minutes . ' * * * * *');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->everyNMinutes(5);
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->everyNMinutes(10);
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->cron('0,30 * * * * *');
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param int $position
     * @param string $value
     *
     * @return Event
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->_expression);
        $segments[$position - 1] = $value;

        return $this->cron(implode(' ', $segments));
    }

    /**
     * Build the execute command.
     *
     * @return string
     */
    public function buildCommand()
    {
        $command = $this->command . ' >> ' . $this->_output . ' 2>&1 &';

        return $this->_user ? 'sudo -u ' . $this->_user . ' ' . $command : $command;
    }

    /**
     * Send email logic.
     *
     * @param MailerInterface $mailer
     * @param array $address
     */
    protected function sendEmail(MailerInterface $mailer, $address)
    {
        $message = $mailer->compose();
        $message->setTextBody(file_get_contents($this->_output))
            ->setSubject($this->getEmailSubject())
            ->setTo($address);

        $message->send();
    }

    /**
     * Return the email subject.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if ($this->_description) {
            return 'Scheduled Job Output (' . $this->_description . ')';
        }

        return 'Scheduled Job Output';
    }

    public function getDefaultOutput()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'NUL';
        } else {
            return '/dev/null';
        }
    }
}
