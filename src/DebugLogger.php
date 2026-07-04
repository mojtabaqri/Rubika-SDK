<?php

namespace RubikaBot;

function isDebugEnabled()
{
    return defined('RUBIKA_DEBUG_ENABLED') && RUBIKA_DEBUG_ENABLED;
}

function debugLog($message, $context = null, $isError = false)
{
    if (!isDebugEnabled()) {
        return;
    }

    $logFile = dirname(__DIR__) . '/log.txt';
    $bugFile = dirname(__DIR__) . '/bug.txt';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[{$timestamp}] {$message}" . PHP_EOL;

    if ($context !== null) {
        $entry .= 'Context:' . PHP_EOL . var_export($context, true) . PHP_EOL;
    }

    $entry .= '---' . PHP_EOL;

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    if ($isError) {
        file_put_contents($bugFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
