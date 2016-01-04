<?php
$schedule->call(function () {
    file_put_contents(alias('@runtime/x.log'), 'data');
});


$schedule->exec('composer --help')->at('14:42')->output(alias('@runtime/cron.log'));

// $schedule->command(' help resource')->output(alias('@runtime/cron.log'));
