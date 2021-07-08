<?php

$start = 1;
$end = 100;

for ($i = $start; $i <= $end; $i++) {
    $result = ($i%3 == 0) ? ($i%5 == 0 ? 'foobar' : 'foo') : ($i%5 == 0 ? 'bar' : $i);
    echo $result.', ';
}