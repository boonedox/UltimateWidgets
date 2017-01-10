<?php

namespace uw;

class Attendees
{
    private $logger;
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getAttendees()
    {
        $dir = sys_get_temp_dir();
        $filename = $dir.'/tmp.xls';
        if (file_exists($filename) && time() - filemtime($filename) < 60) {
            $data = file_get_contents($filename);
        } else {
            $url = getenv('FILE_URL');
            $data = file_get_contents($url);
            file_put_contents($filename, $data);
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filename);
        finfo_close($finfo);
        if ($mime_type == 'text/plain') {
            $stats = $this->getAttendeesFromCsv($filename);
        } else {
            $stats = $this->getAttendeesFromExcel($filename);
        }
        $stats['accepted']++;
        $stats['people']['accepted'][] = 'Jeremiah Johnson';
        $idx = array_search('Jeremiah Johnson', $stats['people']['pending']);
        unset($stats['people']['pending'][$idx]);
        $stats['people']['pending'] = array_values($stats['people']['pending']);
        $last_update_ts = $stats['last_update_ts'];
        if (empty($_GET['debug']) && $last_update_ts < strtotime(date('Y-m-d'))) {
            //$this->logger->addDebug('last update is more than a day old, returning empty data');
            $stats = $this->getDefaultStats();
            if (empty($last_update_ts)) {
                $stats['last_update'] = 'n/a';
            } else {
                $stats['last_update'] = date('M jS, g:ia', $last_update_ts);
            }
        } else {
            $stats['last_update'] = date('M jS, g:ia', $stats['last_update_ts']);
        }
        $stats['last_refresh'] = date('M jS, g:ia');
        $sorter = function ($a, $b) {
            $a = array_pop(explode(' ', $a));
            $b = array_pop(explode(' ', $b));
            return strcmp($a, $b);
        };
        usort($stats['people']['pending'], $sorter);
        usort($stats['people']['accepted'], $sorter);
        usort($stats['people']['declined'], $sorter);
        $stats['people']['total'] = max(
            count($stats['people']['accepted']),
            count($stats['people']['maybe']),
            count($stats['people']['declined'])
        );
        return $stats;
    }
    private function getDefaultStats()
    {
        $stats = array(
            'accepted' => 0,
            'maybe' => 0,
            'declined' => 0,
            'pending' => 0,
            'total' => 0,
            'people' => array(
                'accepted' => array(),
                'maybe' => array(),
                'declined' => array(),
                'total' => 0
            ),
        );
        return $stats;
    }

    private function getAttendeesFromCsv($filename)
    {
        $h = fopen($filename, 'r');
        $headers = fgetcsv($h); // pop headers
        $stats = $this->getDefaultStats();
        $stats['last_update_ts'] = strtotime($headers[3]);
        $ts = strtotime($headers[3]);
        while ($row = fgetcsv($h)) {
            $name = $row[0];
            $response = strtolower($row[1]);
            $response_total = $row[2];
            switch ($response) {
                case 'accepted':
                    break;
                case 'tentative':
                    $response = 'maybe';
                    break;
                case 'declined':
                    break;
                case 'no response':
                    $response = 'pending';
                    break;
                default:
                    $response = null;
                    break;
            }
            if (!is_null($response) && (!is_array($stats['people'][$response]) || !in_array($name, $stats['people'][$response]))) {
                $stats['people'][$response][] = $name;
                $stats[$response]++;
                $stats['total']++;
            }
        }
        return $stats;
    }

    private function getAttendeesFromExcel($filename)
    {
        $objPHPExcel = \PHPExcel_IOFactory::load($filename);

        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $stats = $this->getDefaultStats();
        $stats['accepted'] = $sheetData[2]['B'];
        //$this->logger->addDebug('retrieved file, last update: '.$sheetData[2]['C']);
        $ts = strtotime($sheetData[2]['C']);
        $stats['last_update_ts'] = $ts;

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
        return $stats;

    }
}
