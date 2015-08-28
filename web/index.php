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
$app['monolog']->addDebug('remoteip: '.getenv('HTTP_X_FORWARDED_FOR'));

// Our web handlers

$app->get('/ultimate', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return file_get_contents('../lib/Attendees2.html');
});
$app->get('/fetch', function () use ($app) {
    return file_get_contents('http://sforce.alpha.dev.insidesales.com/do=noauth/atomConfigOption?configKey=Salesforce%3ASimpleSeek&organization_id=00D1a000000ZiE7&token=0051a000000vEjSAAUQOkK8r9UNOXOrLr6MdUrH2tlOvgvfV3LsRyqufNUulItQ0Ql3hwjsAfPtbWEFI5r8oJWpSKtepcRjgavVLXLVnYxOMevBRWYSu8dk4MiGCTPv6');
});
$app->get('/ip', function () use ($app) {
    $ret = "YOUR IP: ".getenv('HTTP_X_FORWARDED_FOR');
    $ip = null;
    $filename = sys_get_temp_dir().'/myip';
    if (!file_exists($filename) || time() - filemtime($filename) > 86400) {
        echo "not cached\n";
        $ip = file_get_contents('http://ipecho.net/plain');
        file_put_contents($filename, $ip);
    }
    $ret .= "MY IP: ".file_get_contents($filename);
    return $ret;
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
