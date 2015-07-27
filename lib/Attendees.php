<?php

namespace uw;

//apache_setenv('FILE_URL', 'https://onedrive.live.com/download.aspx?cid=fa17e0833ee4a1bf&id=documents&resid=FA17E0833EE4A1BF%213043&authkey=!AOYwb97EzOCt0qQ', true);
//putenv('FILE_URL=https://onedrive.live.com/download.aspx?cid=fa17e0833ee4a1bf&id=documents&resid=FA17E0833EE4A1BF%213043&authkey=!AOYwb97EzOCt0qQ');

class Attendees
{
    private $logger;
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function getAttendees()
    {
        $url = getenv('FILE_URL');
        //$url = "https://www.dropbox.com/s/08yq8ymrsj4itc7/UltimateReponses.csv?dl=1";
        $data = file_get_contents($url);
        $dir = sys_get_temp_dir();
        $filename = $dir.'/tmp.xls';
        file_put_contents($filename, $data);
        $finfo = finfo_open(FILEINFO_MIME_TYPE); 
        $mime_type = finfo_file($finfo, $filename);
        finfo_close($finfo);
        if ($mime_type == 'text/plain') {
            $stats = $this->getAttendeesFromCsv($filename);
        } else {
            $stats = $this->getAttendeesFromExcel($filename);
        }
        $stats['last_refresh'] = date('M jS, g:ia');
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
            'last_update' => date('M jS, g:ia'),
        );
    }

    private function getAttendeesFromCsv($filename)
    {
        $h = fopen($filename, 'r');
        fgetcsv($h); // pop headers
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
            if (!is_null($response)) {
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
        $stats['accepted'] = $sheetData[2]['B'];
        //$this->logger->addDebug('retrieved file, last update: '.$sheetData[2]['C']);
        $ts = strtotime($sheetData[2]['C']);

        if (empty($_GET['debug']) && $ts < strtotime(date('Y-m-d'))) {
            //$this->logger->addDebug('last update is more than a day old, returning empty data');
            return array(
                'accepted' => 0,
                'maybe' => 0,
                'people' => array(
                    'accepted' => array(),
                    'maybe' => array(),
                    'declined' => array(),
                    'total' => 0
                ),
                'last_update' => date('M jS, g:ia', $ts),
                'last_refresh' => date('M jS, g:ia')
            );
        }
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
