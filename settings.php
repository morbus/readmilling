<?php

global $conf;

// Specify your Client ID here, as shown on http://readmill.com/you/apps.
// If you don't have it yet, you can get a free one at that URL too. For
// now, the various scripts only require read access, so there's no need to
// specify your client secret or redirect URI configuration.
$conf['client_id'] = 'YOUR-READMILL-CLIENT-ID';

// How long API requests can be cached for.
$conf['cache']['book_readings']      = 86400;
$conf['cache']['user_readings']      = 86400;
$conf['cache']['reading_highlights'] = 86400;
$conf['cache']['highlight_comments'] = 86400;

// Alternatively, put your config in a file not tracked by your VCS.
include_once('settings.local.php');
