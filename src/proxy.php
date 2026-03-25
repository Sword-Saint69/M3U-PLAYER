<?php
// Streaming proxy — forwards stream bytes to the browser
// This is necessary for CORS-restricted streams and IPTV streams

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo 'Invalid or missing URL';
    exit;
}

// Security: only allow http/https
$scheme = strtolower(parse_url($url, PHP_URL_SCHEME));
if (!in_array($scheme, ['http', 'https'])) {
    http_response_code(403);
    echo 'Only HTTP/HTTPS allowed';
    exit;
}

// Build request headers to forward
$forwardHeaders = [];
if (!empty($_SERVER['HTTP_RANGE'])) {
    $forwardHeaders[] = 'Range: ' . $_SERVER['HTTP_RANGE'];
}
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    $forwardHeaders[] = 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'];
} else {
    $forwardHeaders[] = 'User-Agent: Mozilla/5.0 (compatible; M3UPlayer/1.0)';
}

$opts = [
    'http' => [
        'method'          => 'GET',
        'header'          => implode("\r\n", $forwardHeaders),
        'follow_location' => true,
        'timeout'         => 30,
        'ignore_errors'   => true,
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
];

$ctx = stream_context_create($opts);
$stream = @fopen($url, 'rb', false, $ctx);

if (!$stream) {
    http_response_code(502);
    echo 'Could not open stream';
    exit;
}

// Get response headers from the upstream
$meta = stream_get_meta_data($stream);
$responseHeaders = $meta['wrapper_data'] ?? [];

$statusCode  = 200;
$contentType = 'application/octet-stream';

foreach ($responseHeaders as $h) {
    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $h, $m)) {
        $statusCode = (int)$m[1];
    }
    if (preg_match('/^Content-Type:\s*(.+)/i', $h, $m)) {
        $contentType = trim($m[1]);
    }
}

http_response_code($statusCode);
header('Content-Type: ' . $contentType);
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Disable output buffering for real-time streaming
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', 'Off');
while (ob_get_level() > 0) {
    ob_end_flush();
}

// Stream the content
$chunkSize = 8192;
while (!feof($stream)) {
    $chunk = fread($stream, $chunkSize);
    if ($chunk === false) break;
    echo $chunk;
    flush();
}

fclose($stream);
