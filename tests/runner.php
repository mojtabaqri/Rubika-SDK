<?php
$tests = glob(__DIR__ . '/*.php');
foreach ($tests as $test) {
    if (basename($test) === 'runner.php') {
        continue;
    }
    echo "=== " . basename($test) . " ===\n";
    passthru(PHP_BINARY . ' ' . escapeshellarg($test), $exitCode);
    echo "Exit code: {$exitCode}\n\n";
}
