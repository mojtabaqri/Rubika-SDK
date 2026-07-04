<?php

namespace RubikaBot;

function saveIncomingRequest(array $data): void
{
    $logFile = dirname(__DIR__) . '/req.txt';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}]" . PHP_EOL . var_export($data, true) . PHP_EOL;

    $written = @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        $tempFile = sys_get_temp_dir() . '/rubika_req.txt';
        @file_put_contents($tempFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
