<?php

namespace RubikaBot;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile = null)
    {
        $this->logFile = $logFile ?? dirname(__DIR__) . '/req.txt';
    }

    public function saveIncomingRequest(array $data): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}]" . PHP_EOL . var_export($data, true) . PHP_EOL;
        file_put_contents($this->logFile, $entry, LOCK_EX);
    }
}
