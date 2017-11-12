<?php

require '../../vendor/autoload.php';
require '../../config.php';

// open log
$logger = new Katzgrau\KLogger\Logger(LOGFILE);

$aState = [];
$bShowCompleted = $_GET['showCompleted'] == 'true' ? true : false;

header('Content-type: application/json');

// open db
$oDB = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
if ($oDB->connect_error) {
    $logger->critical('Cannot connect to DB from status, exit.');
    print(json_encode([ 'errorReason' => 'DB error' ]));
    exit(0);
}

// query DB
$oResult = $oDB->query('SELECT * FROM ' . DB_PROGRESS_TABLE .
    ($bShowCompleted
        ?
        ''
        :
        ' WHERE progress != total '
    ) .
    ' ORDER BY tstamp DESC');

while ($aRow = $oResult->fetch_array()) {
    $aState[] = [
        'id' => $aRow['id'],
        'start' => $aRow['tstamp'],
        'url' => $aRow['url'],
        'downloaded' => $aRow['progress'],
        'total' => $aRow['total'],
        'state' => $aRow['state']
    ];
}

print(json_encode($aState));

exit(0);

