<?php

namespace uw;

class Weather
{
    private $api_key;

    public function __construct()
    {
        $this->api_key = getenv('WEATHER_API_KEY');
    }

    public function getHourlyWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/weather'.$zip;
        if (!file_exists($filename) || time() - filemtime($filename) > 600) {
            $url = "http://api.wunderground.com/api/{$this->api_key}/hourly/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        }
        return file_get_contents($filename);
    }
    
    public function getRecordWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/almanac'.$zip;
        if (!file_exists($filename) || time() - filemtime($filename) > 600) {
            $url = "http://api.wunderground.com/api/{$this->api_key}/almanac/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        }
        return file_get_contents($filename);
    }
}
