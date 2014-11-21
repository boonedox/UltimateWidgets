<?php

namespace uw;

class Weather
{
    private $api_key;
    private $logger;

    public function __construct($logger)
    {
        $this->api_key = getenv('WEATHER_API_KEY');
        $this->logger = $logger;
    }

    public function getHourlyWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/weather'.$zip;
        if ($_GET['force'] || !file_exists($filename) || time() - filemtime($filename) > 600) {
            //$this->logger->addDebug('fetching hourly data');
            $url = "http://api.wunderground.com/api/{$this->api_key}/hourly/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        } else {
            //$this->logger->addDebug('hourly data is cached, no need to fetch');
        }
        return file_get_contents($filename);
    }

    public function getRecordWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/almanac'.$zip;
        if ($_GET['force'] || !file_exists($filename) || time() - filemtime($filename) > 600) {
            //$this->logger->addDebug('fetching almanac data');
            $url = "http://api.wunderground.com/api/{$this->api_key}/almanac/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        } else {
            //$this->logger->addDebug('almanac data is cached, no need to fetch');
        }
        return file_get_contents($filename);
    }
}
