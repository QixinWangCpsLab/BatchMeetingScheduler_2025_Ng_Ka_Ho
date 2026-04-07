<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

$action = $_GET['action'] ?? '';

if ($action === 'clear') {
    $_SESSION['chat_history'] = [];
    echo json_encode(['ok' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

$apiKey = getenv('GEMINI_API_KEY');
$model = getenv('GEMINI_MODEL') ?: 'gemini-3.1-flash-lite-preview';

if (!$apiKey) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'GEMINI_API_KEY is not set'
    ]);
    exit;
}

$_SESSION['chat_history'][] = [
    'role' => 'user',
    'text' => $userMessage
];

$contents = [];
foreach ($_SESSION['chat_history'] as $msg) {
    $contents[] = [
        'role' => $msg['role'] === 'user' ? 'user' : 'model',
        'parts' => [
            ['text' => $msg['text']]
        ]
    ];
}

$requestBody = [
    'system_instruction' => [
        'parts' => [
            [
                'text' => implode("\n", [
                    'You are the PolyU Reservation Help Chatbot for this reservation system.',
                    'Your job is to help users learn how to use the existing reservation system pages.',
                    'Answer using only the flows visible in the system and do not invent unsupported features.',
                    'If the user asks about something not shown by the system, say that the current system pages do not show that capability.',
                    'Keep answers concise, beginner-friendly, and practical.',
                    'Use short step-by-step instructions when explaining a process.',
                    'Use Markdown only when it improves readability.',
                    'Teacher workflow topics you can explain:',
                    '- Create 1st new meeting',
                    '- Create another round of meeting',
                    '- Check registration status',
                    '- Check allocation results',
                    '- Edit allocation results',
                    'Student workflow topics you can explain:',
                    '- Choose timeslots preferences',
                    '- Check allocation result',
                    '- Check chosen timeslots',
                    'Important details visible in the current pages:',
                    '- Teachers create a meeting by entering meeting title, subject title, teacher name, duration, preferred day count, preferred time-slot count, deadline, meeting day/time periods, and a student ID upload file.',
                    '- Students interact through separate student pages and select timeslot preferences rather than booking directly with the chatbot.',
                    '- The chatbot is informational only and cannot submit forms, change data, or send emails.'
                ])
            ]
        ]
    ],
    'contents' => $contents
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($requestBody),
    CURLOPT_TIMEOUT => 90
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'cURL error: ' . $curlError
    ]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    $errorText = $data['error']['message'] ?? ('Gemini API returned HTTP ' . $httpCode);
    http_response_code($httpCode ?: 500);
    echo json_encode([
        'ok' => false,
        'error' => $errorText
    ]);
    exit;
}

$replyParts = $data['candidates'][0]['content']['parts'] ?? [];
$replyText = '';

foreach ($replyParts as $part) {
    if (isset($part['text'])) {
        $replyText .= $part['text'];
    }
}

if ($replyText === '') {
    $replyText = 'No text response returned.';
}

$_SESSION['chat_history'][] = [
    'role' => 'bot',
    'text' => $replyText
];

echo json_encode([
    'ok' => true,
    'reply' => $replyText
]);
exit;
