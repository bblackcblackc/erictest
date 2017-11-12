<?php

define('MAX_RETRIES',5);
define('RETRY_INTERVAL',5);
define('LOGFILE','../../logs');

define('DB_HOST','mysql');
define('DB_USER','root');
define('DB_PASSWORD','123');
define('DB_NAME','downloader');

define('DB_PROGRESS_TABLE','process');
define('DB_VIDEO_TABLE','processed');

define('HTTP_SERVER_ERROR','Server Error');
define('HTTP_OK','OK');
define('HTTP_BAD_REQUEST','Bad Request');