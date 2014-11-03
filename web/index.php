<?php
ini_set('date.timezone', 'America/Denver');
require('../vendor/autoload.php');

function getData()
{
    $url = 'https://onedrive.live.com/download.aspx?cid=fa17e0833ee4a1bf&id=documents&resid=FA17E0833EE4A1BF%212406&authkey=!AH0R1wo0FrQSf9Y';
    //$url = '../attendingDisc.xlsx';
    $data = file_get_contents($url);
    $dir = sys_get_temp_dir();
    $inputFileName = $dir.'/tmp.xls';
    file_put_contents($inputFileName, $data);
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
    $stats['accepted'] = $sheetData[2]['B'];
    $ts = strtotime($sheetData[2]['C']);

    if ($ts < strtotime(date('Y-m-d'))) {
        return null;
    }
    $stats['last_update'] = date('M jS, g:ia', $ts);
    $stats['last_refresh'] = date('M jS, g:ia');
    $stats['maybe'] = $sheetData[3]['B'];
    $stats['declined'] = $sheetData[4]['B'];
    $stats['pending'] = $sheetData[5]['B'];
    $stats['people']['accepted'] = array();
    $stats['people']['maybe'] = array();
    $stats['people']['declined'] = array();
    for ($x = 8; $x <= count($sheetData); $x++) {
        if (!empty($sheetData[$x]['A'])) {
            $stats['people']['accepted'][] = $sheetData[$x]['A'];
        }
        if (!empty($sheetData[$x]['B'])) {
            $stats['people']['maybe'][] = $sheetData[$x]['B'];
        }
        if (!empty($sheetData[$x]['C'])) {
            $stats['people']['declined'][] = $sheetData[$x]['C'];
        }
    }
    $stats['people']['total'] = max(
        count($stats['people']['accepted']),
        count($stats['people']['maybe']),
        count($stats['people']['declined'])
    );
    return json_encode($stats);
}
$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Our web handlers

$app->get('/ultimate', function () use ($app) {
    $app['monolog']->addDebug('logging output.');
    $html =<<<HTML
<!DOCTYPE html>
<html>
  <head>
  <title id="page_title">Novell Ultimate</title>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <style>
    #hor-minimalist-a {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 12px;
        background: #fff;
        width: 400px;
        padding: 3px;
        border-collapse: collapse;
        text-align: left;
        margin: 20px;
    }
    body {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 12px;
    }
    </style>
    <script type="text/javascript">
        google.load("visualization", "1", {packages:["gauge"]});
        function drawChart(accepted) {

            var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Accepted', accepted]
            ]);

            var options = {
                width: 300, height: 300,
                redFrom: 0, redTo: 4,
                yellowFrom:4, yellowTo: 6,
                greenFrom:6, greenTo: 20,
                minorTicks: 4,
                majorTicks: 4,
                max: 20,
                min: 0
            };

            var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

            chart.draw(data, options);
        }
        function fetchData() {
            $.get('ultimate_data', function(data) {
                if (!data || data == null || !data.accepted) {
                    data = {
                        accepted: 0,
                        people: {
                            accepted: [],
                            maybe: [],
                            declined: []
                        }
                    };
                }
                drawChart(data.accepted);
                drawTable(data);
                setTimeout(fetchData, 60000);
            },
            'json');
        }
        function drawTable(data) {
            var html = '<br><b>Last calendar update</b>: '+ data.last_update + ' -- ';
            html += '<b>Last refresh</b>: '+ data.last_refresh + '<br>';
            html += '<table cellspacing=2 id="hor-minimalist-a" summary="Employee Pay Sheet">';
            html += '<thead> <tr> <th scope="col">Accepted</th>';
            html += '<th scope="col">Tentative</th>';
            html += '<th scope="col">Declined</th>';
            html += '</tr> </thead> <tbody>';
            var a = '';
            var t = '';
            var d = '';
            for (var i = 0; i < data.people.total; i++) {
                a = data.people.accepted.length > i ? data.people.accepted[i] : '';
                t = data.people.maybe.length > i ? data.people.maybe[i] : '';
                d = data.people.declined.length > i ? data.people.declined[i] : '';
                html += '<tr>';
                html += '<td>' + a + '</td>';
                html += '<td>' + t + '</td>';
                html += '<td>' + d + '</td>';
                html += '</tr>';
            }
            html += '</tbody></table>';
            $('#table_div').html(html);
        }
        $(document).ready(function() {
            drawChart(0);
            fetchData();
        });
    </script>
  </head>
  <body>
    <center>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
    <div id="table_div" style="position: relative; top: 160px"></div>
</center>
  </body>
</html>
HTML;
    return $html;
});
$app->get('/ultimate_data', function () use ($app) {
    return getData();
});
$app->get('/', function () use ($app) {
    $app['monolog']->addDebug('logging output.');
    return 'Hello';
});
$app->get('/weather_data', function () use ($app) {
    $zip = $_GET['zip'];
    $filename = sys_get_temp_dir().'/weather'.$zip;
    if (!file_exists($filename) || time() - filemtime($filename) > 600) {
        $url = "http://api.wunderground.com/api/42efd44561264d34/hourly/q/{$zip}.json";
        file_put_contents($filename, file_get_contents($url));
    }
    return file_get_contents($filename);
});
$app->get('/weather', function () use ($app) {
    $app['monolog']->addDebug('logging output.');
    $html =<<<HTML
<!DOCTYPE html>
<html>
  <head>
  <title id="page_title">Novell Weather</title>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Shadows+Into+Light' rel='stylesheet' type='text/css'>
    <style>
    #gt-weather {
        font-family: 'Shadows Into Light', cursive;
        font-size: 24pt;
    }
    #hor-minimalist-a {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 12px;
        background: #fff;
        width: 400px;
        padding: 3px;
        border-collapse: collapse;
        text-align: left;
        margin: 20px;
    }
    body {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 12px;
    }
    </style>
    <script type="text/javascript">
        google.load("visualization", "1", {packages:["corechart"]});
        var chart_width = 800;
        var chart_height = 250;
        var chart_data = [];
        var weather_data = {hourly_forecast: []};

        function drawChart() {
            // Some raw data (not necessarily accurate)
            var chart_data = [];
            //for (var i = 0; i < data.hourly_forecast.length; i++) {
            var gt_weather_set = false;
            for (var i = 0; i < weather_data.hourly_forecast.length; i++) {
                var hour = weather_data.hourly_forecast[i];
                if (!gt_weather_set && hour.FCTTIME.hour == 12) {
                    gt_weather_set = true;
                    $('#twelve_label').html("<span id='gt-weather'>Gametime Weather:</span>");
                    $('#twelve_icon').html("<img height=30 src='"+hour.icon_url+"'>");
                    $('#twelve_forecast').html(
                        hour.condition+", "+hour.temp.english+'&deg; (will feel like ' + hour.feelslike.english + '&deg;), Wind: '+hour.wspd.english+'mph'
                    );
                }
                if (i < 10) {
                    chart_data[chart_data.length] = [
                        hour.FCTTIME.civil,
                        parseInt(hour.temp.english),
                        hour.FCTTIME.civil + "\\n Temperature: " + hour.temp.english + '° (will feel like ' + hour.feelslike.english + '°)',
                        Math.max(parseInt(hour.pop), 0)/100,
                        parseInt(hour.wspd.english)
                    ];
                }
            }
            //var data = google.visualization.arrayToDataTable(chart_data);
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Hour');
            data.addColumn('number', 'Temp');
            data.addColumn({type: 'string', role: 'tooltip'});
            data.addColumn('number', 'Precip %');
            data.addColumn('number', 'Wind Speed');
            data.addRows(chart_data);

            var options = {
                title : 'Hourly Forecast',
                height: chart_height,
                width: chart_width,
                vAxis: {title: "Temperature"},
                vAxes: {
                    0: {format: "#°", gridlines: {color: "#FFF"}},
                    1: {title: "Chance of Precip", textStyle: {color: "red"}, format: "#%", viewWindow: {min: 0}, gridlines: {color: "#FFF"}},
                    2: {title: "Wind", textPosition: "in", textStyle: {color: "orange"}, viewWindow: {max: 28, min: 0}, gridlines: {color: "#CCC"}}
                },
                hAxis: {title: "Hour"},
                seriesType: "line",
                curveType: "function",
                series: {
                    0: {targetAxisIndex: 0},
                    1: {targetAxisIndex: 1},
                    2: {targetAxisIndex: 2}
                }
            };
            var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
        function fetchData() {
            $.get('weather_data?zip=84606', function(data) {
                if (data == null || !data.hourly_forecast) {
                    data = {
                        hourly_forecast: []
                    };
                }
                weather_data = data;
                drawChart();
                setTimeout(fetchData, 10000);
            },
            'json');
        }
        $(document).ready(function() {
            drawChart();
            fetchData();
        });
    </script>
  </head>
  <body>
    <div id="twelve" style="padding-left: 50px"><table><tr><td id="gt-weather">Gametime Weather: </td><td style="padding-top: 6px; padding-left: 6px;" id="twelve_icon"></td><td id="twelve_forecast"></td></table></div>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
    <div style="position: relative; top: 130px; left: 50px "> <a href="http://www.wunderground.com" target="_blank"> <img width="150px" src='http://icons.wxug.com/logos/JPG/wundergroundLogo_4c_horz.jpg'> </a></div>
  </body>
</html>
HTML;
    return $html;
});

$app->run();
