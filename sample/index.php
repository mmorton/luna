<?php

$start = microtime(true);
include_once "../src/luna/Luna.php";

Luna::initialize();

$engine = new LunaEngine(new LunaConfiguration("config.json", "production"));
$engine->initialize();
$engine->processRequest();
$end = microtime(true);

echo sprintf('<br />Whole: %.2f ms, %.2f s<br />', ($end-$start)*1000.0, ($end-$start));

?>