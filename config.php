<?php
declare(strict_types=1);

// Configure these values for your local MySQL installation.
return [
    'db_host' => getenv('PAWMART_DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('PAWMART_DB_PORT') ?: '3306',
    'db_name' => getenv('PAWMART_DB_NAME') ?: 'pawmart',
    'db_user' => getenv('PAWMART_DB_USER') ?: 'root',
    'db_pass' => getenv('PAWMART_DB_PASS') ?: '',
];
