#!/usr/bin/php
<?php
require 'src/Chartbeat/Chartbeat.php';
require 'src/Chartbeat/Exception.php';

echo "Chartbeat example script\n";


if (!isset($argv[1]) || !isset($argv[2])) {
  echo "Usage: $argv[0] <your host> <your API key> \n";
  die;
}

$host = $argv[1];
$apiKey = $argv[2];
$cb = new Chartbeat\Chartbeat($host, $apiKey);

echo 'Monthly max people on the site: ' . $cb->getMonthlyMaxPeople() . "\n"; 

