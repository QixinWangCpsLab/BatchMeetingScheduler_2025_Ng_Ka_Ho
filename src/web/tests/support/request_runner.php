<?php
declare(strict_types=1);

$specPath = $argv[1] ?? '';
if ($specPath === '' || !is_file($specPath)) {
    fwrite(STDERR, "Missing request spec.\n");
    exit(2);
}

$spec = json_decode(file_get_contents($specPath), true, 512, JSON_THROW_ON_ERROR);
$webRoot = realpath(dirname(__DIR__, 2));
$script = $spec['script'] ?? '';

if ($webRoot === false || $script === '') {
    fwrite(STDERR, "Invalid request spec.\n");
    exit(2);
}

$scriptPath = $webRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $script);
if (!is_file($scriptPath)) {
    fwrite(STDERR, "Script not found: {$script}\n");
    exit(2);
}

chdir($webRoot);

$_GET = $spec['get'] ?? [];
$_POST = $spec['post'] ?? [];
$_FILES = $spec['files'] ?? [];
$_REQUEST = array_merge($_GET, $_POST);

$requestMethod = strtoupper($spec['method'] ?? (!empty($_POST) ? 'POST' : 'GET'));
$_SERVER['DOCUMENT_ROOT'] = $webRoot;
$_SERVER['REQUEST_METHOD'] = $requestMethod;
$_SERVER['SCRIPT_FILENAME'] = $scriptPath;
$_SERVER['SCRIPT_NAME'] = '/' . str_replace('\\', '/', $script);
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
$_SERVER['QUERY_STRING'] = http_build_query($_GET);
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$response = [
    'statusCode' => 200,
    'headers' => [],
    'output' => '',
    'fatalError' => null,
];

ob_start();

register_shutdown_function(static function () use (&$response): void {
    $response['statusCode'] = http_response_code() ?: 200;
    $response['headers'] = headers_list();
    $response['output'] = ob_get_contents() ?: '';

    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        $response['fatalError'] = $error['message'];
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
});

try {
    require $scriptPath;
} catch (Throwable $throwable) {
    http_response_code(500);
    $response['fatalError'] = $throwable->getMessage();
    echo $throwable;
}
