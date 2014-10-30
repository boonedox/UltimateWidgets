<?php
require('../vendor/autoload.php');

function getData()
{
    $url = 'https://sc7acq-ch3301.files.1drv.com/y2mFNc5dptk-Z6-mZx2NPkDn5Z4BnjtcxOmSg2yAkMkD-IdOzfdZuPem0tz2NEBYZGNnbfIaXkRb7a6E9RKgtx5ZlhnIZJfrYzGsrWrR4ImFt-XAoqAG6DInCnSYgO3irpv/attendingDisc.xlsx?download&psid=1';
    $url = '../attendingDisc.xlsx';
    $data = file_get_contents($url);
    $dir = sys_get_temp_dir();
    $inputFileName = $dir.'/tmp.xls';
    file_put_contents($inputFileName, $data);
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
    $stats['accepted'] = $sheetData[2]['B'];
    $stats['last_update'] = date('M jS, g:ia', strtotime($sheetData[2]['C']));
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
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <style>
    #hor-minimalist-a {
        font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
        font-size: 12px;
        background: #fff;
        /*width: 480px;*/
        padding: 3px;
        border-collapse: collapse;
        text-align: left;
        margin: 20px;
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
                minorTicks: 6,
                max: 20,
                min: 0
            };

            var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

            chart.draw(data, options);
        }
        function fetchData() {
            $.get('ultimate_data', function(data) {
                drawChart(data.accepted);
                drawTable(data);
                setTimeout(fetchData, 10000);
            },
            'json');
        }
        function drawTable(data) {
            var html = 'Last update: '+ data.last_update + '<br><br>';
            html += '<table id="hor-minimalist-a" summary="Employee Pay Sheet">';
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

$app->run();
