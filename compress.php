<?php
/**
 * Copyright 2012 James Lawrence
 * Simple gzip compression for static content, with cache
 * Very simple code, no rights agreements, etc.  Use it for whatever you need
 */

// Change constants to suit your needs
define('COMPRESSION_CACHE_ENABLED', true); // Shall we cache compressed content?
define('COMPRESSION_CACHE_PATH', './cache'); // Path for cache
define('COMPRESSION_CACHE_LIFETIME', 86400); // Time in seconds for cache to live (86400 = 60 * 60 * 24)
//define('DEBUGGING', true);

// Get accepted encodings...
$_lowerheader = strtolower($_SERVER['HTTP_ACCEPT_ENCODING']);

if (substr_count($_lowerheader, 'gzip'))
    $encoding = 'gzip';
elseif (substr_count($_lowerheader, 'deflate'))
    $encoding = 'deflate';

// Get path to requested file ( THIS MIGHT BE BUGGY, UNFORTUNATELY PHP SUCKS :( )
$_path = $_SERVER['DOCUMENT_ROOT'] . urldecode((defined($_SERVER['REQUEST_URL']) ? $_SERVER['REQUEST_URL'] : $_SERVER['REQUEST_URI']));

if($_path == __FILE__)
    die('You can\'t access compress.php through compress.php :O');

// Update: no longer deprecated in PHP 7
$_ftype = mime_content_type($_path);
if (defined('DEBUGGING'))
    print('<span>path:' . $_path . ',mime: ' . $_ftype . '</span><br/>');

// If we have a query string, assume this is dynamic content and switch to gzip output buffer
if ($_SERVER['QUERY_STRING']) {
    if (defined('DEBUGGING'))
        print('<span>Dynamic content, switching to GZIP output buffer</span><br/>');
    ob_start('ob_gzhandler');
    return;
}

// Hash of filename for cache path (SHOULD be fine in most circumstances, you'll want to make sure that none of your
// static file paths have hash clashes (or change the path, maybe md5($_path).sha($_path)? but more CPU time...)
$hash = md5($_path);

// Check cache first...
$cpath = constant('COMPRESSION_CACHE_PATH') . '/' . $hash . '.' . $encoding . '.gz';

// Generate cache file...
if (!constant('COMPRESSION_CACHE_ENABLED') || !file_exists($cpath) || (time() - filemtime($cpath)) >= constant('COMPRESSION_CACHE_LIFETIME')) {
    $contents = file_get_contents($_path);
    switch($encoding)
    {
        case 'deflate':
            $contents = gzdeflate($contents, 9);
            break;
        case 'gzip':
            $contents = gzencode($contents, 9);
            break;
        default: // Do nothing, return contents as they are
            break;
    }

    if (constant('COMPRESSION_CACHE_ENABLED') && isset($encoding))
        file_put_contents($cpath, $contents);
} else
    $contents = file_get_contents($cpath);

// Return cache'd file
header('Content-Type: ' . $_ftype);
if(isset($encoding))
    header('Content-Encoding: ' . $encoding);
header('Content-Length: ' . strlen($contents));
header('Vary: Accept-Encoding');
die($contents);
?>
