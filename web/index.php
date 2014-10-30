<?php
exit;
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
    $stats['last_update'] = date('Y-m-d H:i:s', strtotime($sheetData[2]['C']));
    $stats['maybe'] = $sheetData[3]['B'];
    $stats['declined'] = $sheetData[4]['B'];
    $stats['pending'] = $sheetData[5]['B'];
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
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript">
        google.load("visualization", "1", {packages:["gauge"]});
        function drawChart(data) {
            data = 0;

            var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Accepted', 0]
            ]);

            var options = {
                width: 800, height: 400,
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

        $(document).ready(function() {
            drawChart(0);
            $.get('ultimate_data', function(data) {
                alert(data);
            },
            'json');
        });
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
  </body>
</html>
HTML;
});
$app->get('/ultimate_data', function () use ($app) {
    return getData();
});
$app->get('/', function () use ($app) {
    $app['monolog']->addDebug('logging output.');
    return 'Hello';
});

$app->run();
