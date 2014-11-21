<?php
ini_set('date.timezone', 'America/Denver');
require('../vendor/autoload.php');
require('../lib/Weather.php');
require('../lib/Attendees.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Our web handlers

$app->get('/ultimate', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return file_get_contents('../lib/Attendees.html');
});
$app->get('/ultimate_data', function () use ($app) {
    $u = new uw\Attendees($app['monolog']);
    return json_encode($u->getAttendees());
});
$app->get('/', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return 'Hello';
});
$app->get('/weather_data', function () use ($app) {
    $w = new uw\Weather($app['monolog']);
    $hourly_data = json_decode($w->getHourlyWeatherForZip($_GET['zip']));
    $gametime_forecast_date = null;
    foreach ($hourly_data->hourly_forecast as $hour) {
        if ($hour->FCTTIME->hour == '12') {
            $gametime_forecast_date = date('l, M jS', strtotime($hour->FCTTIME->pretty)) . ', 12pm';
            break;
        }
    }
    $record_data = json_decode($w->getRecordWeatherForZip($_GET['zip']));
    $ret = array(
        'hourly' => $hourly_data,
        'record' => $record_data,
        'last_refresh' => date('M jS, g:i:s a'),
        'gametime_forecast_date' => $gametime_forecast_date
    );
    return json_encode($ret);
});
$app->get('/weather', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return file_get_contents('../lib/Weather.html');
});

$app->run();
