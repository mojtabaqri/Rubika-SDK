<?php

namespace RubikaBot;

function saveIncomingRequest(array $data): void
{
    $logFile = __DIR__ . '/../req.txt';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}]" . PHP_EOL . var_export($data, true) . PHP_EOL;
    file_put_contents($logFile, $entry, LOCK_EX);
}
