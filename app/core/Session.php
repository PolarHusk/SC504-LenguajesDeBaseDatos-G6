<?php

declare(strict_types=1);

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $sessionPath = dirname(__DIR__, 2) . '/tmp/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }

    session_save_path($sessionPath);
    session_start();
}
