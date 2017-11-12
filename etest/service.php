<?php

function setDownloadState($oDatabase, $iOperationID, $iDownloaded, $iDownloadTotal, $sState) {
    $oDatabase->query('UPDATE `' . DB_PROGRESS_TABLE . '` SET ' .
            ((($iDownloadTotal == 0) || ($iDownloaded == 0))
            ?
            ''
            :
            '`progress` = ' . intval($iDownloaded) .
            ', `total` = ' . intval($iDownloadTotal) . ', '
            ) .
        '`state` = "' . $oDatabase->real_escape_string($sState) . '"' .
        ' WHERE `id` = ' . $iOperationID);
}