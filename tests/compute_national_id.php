<?php
$digits = [1, 0, 0, 0, 0, 0, 0, 0, 0];
$sum = 0;
for ($i = 0; $i < 9; $i++) {
    $sum += $digits[$i] * (10 - $i);
}
$remainder = $sum % 11;
$controlDigit = $remainder < 2 ? $remainder : 11 - $remainder;
echo implode($digits) . $controlDigit . PHP_EOL;
