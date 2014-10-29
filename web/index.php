<?php

require('../vendor/autoload.php');
  //$size = strlen(file_get_contents('https://onedrive.live.com/embed?cid=FA17E0833EE4A1BF&resid=FA17E0833EE4A1BF%212406&authkey=AH0R1wo0FrQSf9Y&em=2&wdAllowInteractivity=False&Item=%27Sheet1%27!A1%3AC25&wdHideGridlines=True'));

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
