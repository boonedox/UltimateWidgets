<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["gauge"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {

        var data = google.visualization.arrayToDataTable([
          ['Label', 'Value'],
          ['Accepted', 7]
        ]);

        var options = {
          width: 800, height: 400,
          redFrom: 0, redTo: 25,
          yellowFrom:25, yellowTo: 50,
          greenFrom:50, greenTo: 100,
          minorTicks: 6,
          max: 20,
          min: 0
        };

        var chart = new google.visualization.Gauge(document.getElementById('chart_div'));

        chart.draw(data, options);

        setInterval(function() {
          data.setValue(0, 1, 40 + Math.round(60 * Math.random()));
          chart.draw(data, options);
        }, 13000);
        setInterval(function() {
          data.setValue(1, 1, 40 + Math.round(60 * Math.random()));
          chart.draw(data, options);
        }, 5000);
        setInterval(function() {
          data.setValue(2, 1, 60 + Math.round(20 * Math.random()));
          chart.draw(data, options);
        }, 26000);
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 400px; height: 120px;"></div>
  </body>
</html>
<?php
exit;
require('../vendor/autoload.php');
  //$size = strlen(file_get_contents('https://onedrive.live.com/embed?cid=FA17E0833EE4A1BF&resid=FA17E0833EE4A1BF%212406&authkey=AH0R1wo0FrQSf9Y&em=2&wdAllowInteractivity=False&Item=%27Sheet1%27!A1%3AC25&wdHideGridlines=True'));
    $url = '../attendingDisc.xlsx';
    $data = file_get_contents($url);
    $dir = sys_get_temp_dir();
    $inputFileName = $dir.'/tmp.xls';
    file_put_contents($inputFileName, $data);
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);



    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
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
    "<pre>".print_r($stats);
    exit;

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Our web handlers

$app->get('/ultimate', function() use($app) {
    $app['monolog']->addDebug('logging output.');
    $url = 'https://sc7acq-ch3301.files.1drv.com/y2mFNc5dptk-Z6-mZx2NPkDn5Z4BnjtcxOmSg2yAkMkD-IdOzfdZuPem0tz2NEBYZGNnbfIaXkRb7a6E9RKgtx5ZlhnIZJfrYzGsrWrR4ImFt-XAoqAG6DInCnSYgO3irpv/attendingDisc.xlsx?download&psid=1';
    $url = '../attendingDisc.xlsx';
    $data = file_get_contents($url);
    $dir = sys_get_temp_dir();
    $inputFileName = $dir.'/tmp.xls';
    file_put_contents($inputFileName, $data);
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
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
});
$app->get('/ultimate_data', function() use($app) {
    $app['monolog']->addDebug('logging output.');
    $url = 'https://sc7acq-ch3301.files.1drv.com/y2mFNc5dptk-Z6-mZx2NPkDn5Z4BnjtcxOmSg2yAkMkD-IdOzfdZuPem0tz2NEBYZGNnbfIaXkRb7a6E9RKgtx5ZlhnIZJfrYzGsrWrR4ImFt-XAoqAG6DInCnSYgO3irpv/attendingDisc.xlsx?download&psid=1';
    $url = '../attendingDisc.xlsx';
    $data = file_get_contents($url);
    $dir = sys_get_temp_dir();
    $inputFileName = $dir.'/tmp.xls';
    file_put_contents($inputFileName, $data);
    $objPHPExcel = PHPExcel_IOFactory::load($inputFileName);



    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
    return "<pre>".print_r($sheetData, true);
});
$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  return 'Hello';
});

$app->run();

?>
