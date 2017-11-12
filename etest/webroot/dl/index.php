<?php

require '../../vendor/autoload.php';
require '../../config.php';
require '../../service.php';

// fetch parameters
$sURL = $_GET['url'];
$sHash = $_GET['md5'];

// open log
$logger = new Katzgrau\KLogger\Logger(LOGFILE);

// check if url or hash is empty
if (empty($sURL) or empty($sHash)) {
    header($_SERVER["SERVER_PROTOCOL"] . " 400 " . HTTP_BAD_REQUEST);
    $logger->error('Wrong request, exit.');
    exit(0);
}

$logger->info('Correctly started with URL = ' . $sURL . ', hash = ' . $sHash);

// open db
$oDB = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
if ($oDB->connect_error) {
    header($_SERVER["SERVER_PROTOCOL"] . " 500 " . HTTP_SERVER_ERROR);
    $logger->critical('Cannot connect to DB, exit.');
    exit(0);
}

$oDB->query('INSERT INTO `' . DB_PROGRESS_TABLE . '` (`url`, `hash`, `progress`, `total`) VALUES ' .
    '("' . $oDB->real_escape_string($sURL). '", "' . $oDB->real_escape_string($sHash). '", 0, 0)');

$iOperationID = $oDB->insert_id;

// trying to download
for($iRetry = 0; $iRetry < MAX_RETRIES; $iRetry++) {

    $oCurl = curl_init();
    curl_setopt($oCurl, CURLOPT_URL, $sURL);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($oCurl, CURLOPT_PROGRESSFUNCTION, function ($cp, $iTotalDL, $iCurrentDL, $iTotalUL, $iCurrentUL) use ($iOperationID, $oDB) {
        setDownloadState($oDB, $iOperationID, $iCurrentDL, $iTotalDL, 'downloading');
    });
    curl_setopt($oCurl, CURLOPT_NOPROGRESS, false);
    curl_setopt($oCurl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($oCurl, CURLOPT_HEADER, 0);
    curl_setopt($oCurl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    $sResult = curl_exec($oCurl);

    $iError = curl_errno($oCurl);
    $sError = curl_error($oCurl);

    if ($iError == CURLE_OK) {
        // all ok

        $logger->info('[' . $iOperationID . '] ' . $sURL . ' correctly downloaded.');

        // state update
        setDownloadState($oDB, $iOperationID, 0, 0, 'hashing');

        // need to check hash
        $sDLHash = md5($sResult);
        $logger->info('[' . $iOperationID . '] Downloaded file hash = ' . $sDLHash . '; required file hash = ' . $sHash);

        if ($sDLHash == $sHash) {
            $logger->info('[' . $iOperationID . '] Hash correct.');

            // save file to temp dir
            $sFName = tempnam(sys_get_temp_dir(), 'vid');
            $fHandle = fopen($sFName,'w');
            fwrite($fHandle,$sResult);
            fclose($fHandle);
            $logger->info('[' . $iOperationID . '] Saved to temporary file ' . $sFName);

            // check if file is supported video
            $oFFProbe = \FFMpeg\FFProbe::create();
            if (!$oFFProbe->isValid($sFName)) {
                // no. sad but true.
                $logger->alert('[' . $iOperationID . '] ' . $sURL . ' is not video file or format is not supported. Exit.');
                setDownloadState($oDB, $iOperationID, 0, 0, 'completed, not video');
            } else {
                // video is correct. get info
                $aVidInfo = $oFFProbe
                            ->streams($sFName)
                            ->videos()
                            ->first()
                            ->all();

                $iWidth = intval($aVidInfo['width']);
                $iHeight = intval($aVidInfo['height']);
                $iBitRate = intval($aVidInfo['bit_rate']);

                // log
                $logger->info('[' . $iOperationID. '] ' . $sURL . ' is video with ' . $iWidth . 'x' . $iHeight .
                            ' dimensions and ' . $iBitRate . ' kbps bitrate.');

                // db
                $oDB->query('INSERT INTO `' . DB_VIDEO_TABLE . '` (`url`, `width`, `height`, `bitrate`) VALUES ' .
                    '("' . $oDB->real_escape_string($sURL). '", ' . $iWidth . ', ' . $iHeight . ', ' . $iBitRate . ')');

                // state
                setDownloadState($oDB, $iOperationID, 0, 0, 'completed');
            }

            // unlink temp file
            unlink($sFName);
        } else {
            $logger->critical('[' . $iOperationID . '] ' . $sURL . ' has wrong hash. Exit.');
            setDownloadState($oDB, $iOperationID, 0, 0, 'completed, wrong hash');
        }

        header($_SERVER["SERVER_PROTOCOL"] . " 200 " . HTTP_OK);
        curl_close($oCurl);
        $oDB->close();
        exit(0);
    } elseif (($iError != CURLE_OPERATION_TIMEDOUT) && ($iError != CURLE_PARTIAL_FILE)) {
        // got unrecoverable error
        $logger->error('[' . $iOperationID . '] ' . $sURL . ' cannot be downloaded with error ' . $iError . ', ' . $sError);
        setDownloadState($oDB, $iOperationID, 0, 0, 'unrecoverable error');
        header($_SERVER["SERVER_PROTOCOL"] . " 500 " . HTTP_SERVER_ERROR);
        curl_close($oCurl);
        $oDB->close();
        exit(0);
    }

    sleep(RETRY_INTERVAL);
}

// retries count exceeded
$logger->error('[' . $iOperationID . '] ' . $sURL . ' cannot be downloaded with error ' . $iError . ', ' . $sError);
setDownloadState($oDB, $iOperationID, 0, 0, 'retries limit exceeded');
header($_SERVER["SERVER_PROTOCOL"] . " 500 " . HTTP_SERVER_ERROR);
curl_close($oCurl);
$oDB->close();
exit(0);
