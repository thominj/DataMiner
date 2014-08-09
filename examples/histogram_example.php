<?php
require_once('../src/thomin/DataMiner/Histogram.php');

/** Basic use case **/
$bins = [0, 1, 2, 3];
$data = $data = [ -1, 0, 0.5, 1, 3, 4 ];

$histogram = new \thomin\DataMiner\Histogram($bins, $data);
$result = $histogram->getResult();

echo "Basic use:\n";
print_r($result);

/** Online mode **/
$histogram = new \thomin\DataMiner\Histogram($bins);

$histogram->addData($data);
echo "Online mode, first set:\n";
print_r($histogram->getResult());

// Add more data
$histogram->addData($data);
echo "Online mode, second set:\n";
print_r($histogram->getResult());

/** Resume an interrupted analysis **/
$old_result = array(
		'less' => 2,
		0 => 4,
		1 => 2,
		2 => 0,
		3 => 4
);

$histogram = new \thomin\DataMiner\Histogram();
$histogram->preload($old_result);
$histogram->addData($data);

echo "Resumed from earlier results:\n";
print_r($histogram->getResult());