<?php
// Centralised configuration powered by environment variables with safe defaults.
// Adjust the defaults or set env vars (e.g. in a .env file or server config) for production.
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'username' => getenv('DB_USERNAME') ?: 'appuser',
        'password' => getenv('DB_PASSWORD') ?: 'apppassword',
        'name' => getenv('DB_DATABASE') ?: 'capstone_project',
    ],
    'mail' => (function () {
        $studentDomain = getenv('MAIL_STUDENT_DOMAIN') ?: '@connect.polyu.hk';
        return [
            'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
            'port' => (int) (getenv('MAIL_PORT') ?: 465),
            'encryption' => getenv('MAIL_ENCRYPTION') ?: 'ssl',
            'username' => getenv('MAIL_USERNAME') ?: '@gmail.com',
            'password' => getenv('MAIL_PASSWORD') ?: '',
            'from_address' => getenv('MAIL_FROM_ADDRESS') ?: (getenv('MAIL_USERNAME') ?: 'do-not-reply@example.com'),
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'do-not-reply',
            'student_domain' => $studentDomain,
            'result_domain' => getenv('MAIL_RESULT_DOMAIN') ?: $studentDomain,
        ];
    })(),
    'app' => [
        'base_url' => rtrim(getenv('APP_BASE_URL') ?: 'http://www2.comp.polyu.edu.hk/~//web', '/'),
    ],
];
