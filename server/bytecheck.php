<?php
$b = file_get_contents(__DIR__ . "/raw-fail.txt");
echo "len: " . strlen($b) . "\n";
echo "full hex:\n" . chunk_split(bin2hex($b), 64) . "\n";
echo "json: " . (json_decode($b) !== null ? "yes" : "NO") . "\n";
?>