<?php

namespace uw;

class Attendees
{
    public function getAttendees()
    {
        $url = getenv('EXCEL_FILE_URL');
        $data = file_get_contents($url);
        $dir = sys_get_temp_dir();
        $inputFileName = $dir.'/tmp.xls';
        file_put_contents($inputFileName, $data);
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);

        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
        $stats['accepted'] = $sheetData[2]['B'];
        $ts = strtotime($sheetData[2]['C']);

        if ($ts < strtotime(date('Y-m-d'))) {
            $t = array(
                'accepted' => 0,
                'people' => array(
                    'accepted' => array(),
                    'maybe' => array(),
                    'declined' => array()
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
        $stats['people']['total'] = max(
            count($stats['people']['accepted']),
            count($stats['people']['maybe']),
            count($stats['people']['declined'])
        );
        return $stats;
    }
}
