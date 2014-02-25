<?php
require __DIR__ . '/../benchmark.php';

$vector = Icecave\Collections\Vector::create(1);

Benchmark::run(
    50000,
    null,
    function ($i) use ($vector) {
        $vector->tryFront($element);
    }
);
