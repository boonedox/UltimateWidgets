<?php

namespace uw;

class Weather
{
    public function getHourlyWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/weather'.$zip;
        if (!file_exists($filename) || time() - filemtime($filename) > 600) {
            $url = "http://api.wunderground.com/api/42efd44561264d34/hourly/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        }
        return file_get_contents($filename);
    }
    public function getRecordWeatherForZip($zip)
    {
        $filename = sys_get_temp_dir().'/almanac'.$zip;
        if (!file_exists($filename) || time() - filemtime($filename) > 600) {
            $url = "http://api.wunderground.com/api/42efd44561264d34/almanac/q/{$zip}.json";
            file_put_contents($filename, file_get_contents($url));
        }
        return file_get_contents($filename);
    }
}
