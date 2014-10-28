<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

// Our web handlers

$app->get('/', function() use($app) {
  $app['monolog']->addDebug('logging output.');
  $size = strlen(file_get_contents('https://onedrive.live.com/embed?cid=FA17E0833EE4A1BF&resid=FA17E0833EE4A1BF%212406&authkey=AH0R1wo0FrQSf9Y&em=2&wdAllowInteractivity=False&Item=%27Sheet1%27!A1%3AC25&wdHideGridlines=True'));
  return 'Hello ('.$size.')';
});

$app->run();

?>
