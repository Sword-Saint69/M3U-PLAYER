<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function parseM3U($content) {
    $channels = [];
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $current = null;

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        if (strpos($line, '#EXTINF') === 0) {
            $current = [
                'name'  => '',
                'logo'  => '',
                'group' => '',
                'url'   => '',
            ];
            // Parse tvg-name
            if (preg_match('/tvg-name="([^"]*)"/', $line, $m)) {
                $current['name'] = $m[1];
            }
            // Parse tvg-logo
            if (preg_match('/tvg-logo="([^"]*)"/', $line, $m)) {
                $current['logo'] = $m[1];
            }
            // Parse group-title
            if (preg_match('/group-title="([^"]*)"/', $line, $m)) {
                $current['group'] = $m[1];
            }
            // Fallback: name after comma
            if (empty($current['name']) && preg_match('/#EXTINF[^,]*,(.+)$/', $line, $m)) {
                $current['name'] = trim($m[1]);
            }
        } elseif ($current !== null && !empty($line) && strpos($line, '#') !== 0) {
            $current['url'] = $line;
            $channels[] = $current;
            $current = null;
        }
    }
    return $channels;
}

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['type'] ?? 'text';

if ($type === 'url') {
    $url = filter_var($input['url'] ?? '', FILTER_VALIDATE_URL);
    if (!$url) {
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }
    $ctx = stream_context_create(['http' => [
        'timeout'     => 15,
        'user_agent'  => 'M3UPlayer/1.0',
        'follow_location' => true,
    ]]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content === false) {
        echo json_encode(['error' => 'Failed to fetch M3U from URL']);
        exit;
    }
} elseif ($type === 'file') {
    $content = $input['content'] ?? '';
} else {
    $content = $input['content'] ?? '';
}

if (empty($content)) {
    echo json_encode(['error' => 'No content provided']);
    exit;
}

$channels = parseM3U($content);
echo json_encode(['channels' => $channels, 'count' => count($channels)]);
