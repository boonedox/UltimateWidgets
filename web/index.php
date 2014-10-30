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
  <link rel="stylesheet" href="http://www.smashingmagazine.com/wp-content/themes/smashing-magazine/stylesheets/main.min.css?ver=2014.15.0">
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript">
        google.load("visualization", "1", {packages:["gauge"]});
        function drawChart(accepted) {

            var data = google.visualization.arrayToDataTable([
                ['Label', 'Value'],
                ['Accepted', accepted]
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
        function fetchData() {
            $.get('ultimate_data', function(data) {
                drawChart(data.accepted);
                setTimeout(fetchData, 60000);
            },
            'json');
        }
        $(document).ready(function() {
            drawChart(0);
            fetchData();
        });
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
    <table id="hor-minimalist-a" summary="Employee Pay Sheet">
<thead>
<tr>
<th scope="col">Employee</th>
<th scope="col">Salary</th>
<th scope="col">Bonus</th>
<th scope="col">Supervisor</th>
</tr>
</thead>
<tbody>
<tr>
<td>Stephen C. Cox</td>
<td>$300</td>
<td>$50</td>
<td>Bob</td>
</tr>
<tr>
<td>Josephin Tan</td>
<td>$150</td>
<td>-</td>
<td>Annie</td>
</tr>
<tr>
<td>Joyce Ming</td>
<td>$200</td>
<td>$35</td>
<td>Andy</td>
</tr>
<tr>
<td>James A. Pentel</td>
<td>$175</td>
<td>$25</td>
<td>Annie</td>
</tr>
</tbody>
</table>
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
