<?php
require __DIR__ . '/../benchmark.php';

$vector = new Icecave\Collections\Vector;

for ($i = 0; $i < 2000; ++$i) {
    $vector->pushBack($i);
}

Benchmark::run(
    1000,
    null,
    function ($i) use ($vector) {
        $vector->elements();
    }
);
