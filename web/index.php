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
$app->get('/favicon.ico', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return file_get_contents('../favicon.ico');
});

$app->get('/ultimate', function () use ($app) {
    //$app['monolog']->addDebug('logging output.');
    return file_get_contents('../lib/Attendees2.html');
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
    $hourly_data->hourly_forecast = $hourly_data->hourly->data;
    $gametime_forecast_date = null;
    $icon_map = array(
       "clear-day" => "clear-day",
       "clear-night" => "clear-night",
       "rain" => "rain",
       "snow" => "snow",
       "sleet" => "sleet",
       "wind" => "wind",
       "fog" => "fog",
       "cloudy" => "cloudy",
       "partly-cloudy-day" => "partly-cloudy-day",
       "partly-cloudy-night" => "partly-cloudy-night",
    );
    $almanac = array(
        "temp_high" => array(
            "record" => array("F" => 0),
            "F" => true
        ),
        "temp_low" => array(
            "record" => array("F" => 100),
            "F" => true
        ),
    );
    foreach ($hourly_data->hourly_forecast as &$hour) {
        if (isset($icon_map[$hour->icon])) {
            $hour->icon_url = "/images/black/".$icon_map[$hour->icon].".png";
        } else {
            $hour->icon_url = "/images/black/unknown.png";
        }
        $h = date('H', $hour->time);
        $hour->FCTTIME = new stdClass();
        $hour->temp = new stdClass();
        $hour->feelslike = new stdClass();
        $hour->wspd = new stdClass();
        $hour->FCTTIME->hour = $h;
        $hour->FCTTIME->civil = date('g:i A', $hour->time);
        $hour->condition = $hour->summary;
        $hour->temp->english = $hour->temperature;
        $almanac["temp_high"]["record"]["F"] = max($almanac["temp_high"]["record"]["F"], $hour->temperature+20);
        $almanac["temp_low"]["record"]["F"] = min($almanac["temp_high"]["record"]["F"], $hour->temperature-20);
        $hour->feelslike->english = $hour->apparentTemperature;
        $hour->wspd->english = $hour->windSpeed;
        $hour->pop = $hour->precipProbability*100;

        if ($h == '12' && is_null($gametime_forecast_date)) {
            $gametime_forecast_date = date('l, M jS', $hour->time) . ', 12pm';
        }
    }
    //$record_data = json_decode($w->getRecordWeatherForZip($_GET['zip']));
    $ret = array(
        'hourly' => $hourly_data,
        'record' => array('almanac' => $almanac),
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
