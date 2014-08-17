#DataMiner

Data Miner is a set of data mining tools written in PHP. It is designed for easy integration with existing websites.

##Installation

You can install Data Miner very easily with [Composer](https://getcomposer.org/).

Include this in your composer.json and then run composer install:

"thominj/data-miner": "1.*"

##Histogram


Histograms bin data by counting the number of times a value occurs within a given range. The bin ranges are given by an array where each value corresponds to the left endpoint (inclusive) of the range. The last value is the right end point of the last bin.

For example:

```php
$bins = [ 0, 1, 2, 3];
$data = [ -1, 0, 0.5, 1, 3, 4 ];

$histogram = new Histogram($bins, $data);
$result = $histogram->getResult();

print_r($result);
```

This will output:

```
Array
(
    [less] => 1
    [0] => 2
    [1] => 1
    [2] => 0
    [3] => 2
)
```
The last bin labeled '3' contains all values greater than or equal to 3.

###Setting Bins and Adding Data

You can set the bins and add data in lots of different ways. Each of these will give the same result:

```php
// Set bins and add data in the constructor
$histogram = new Histogram($bins, $data);

// Set bins in the constructor, add data later
$histogram = new Histogram($bins);
$histogram->addData($data);

// Set bins and add data after instancing the histogram
$histogram = new Histogram();
$histogram->setBins($bins);
$histogram->addData($data);
```

###Online Mode

You can also run in online mode to repeatedly add data sets. Histogram will sort the data as it comes in, accumulating the results and letting you check them whenever you like:

```php
// Online mode
$histogram = new Histogram($bins);

// Add some data
$histogram->addData($data);

// Get the result
print_r($histogram->getResult());

// Add some more data
$histogram->addData($data);

// Get the new result
print_r($histogram->getResult());
```

This will give:

```
Array
(
    [less] => 1
    [0] => 2
    [1] => 1
    [2] => 0
    [3] => 2
)
Array
(
    [less] => 2
    [0] => 4
    [1] => 2
    [2] => 0
    [3] => 4
)
```

###Save and Resume

You can even save a set of results, then pre-load it before adding more data. This lets you save results in the middle of a long process, re-load them, and pick up where you left off.

```php
$old_result = array(
  'less' => 2,
  0 => 4,
  1 => 2, 
  2 => 0,
  3 => 4
);

$histogram = new Histogram();
$histogram->preload($old_result);
$histogram->addData($data);

print_r($histogram->getResult());
```

This outputs:

```
Array
(
    [less] => 3
    [0] => 6
    [1] => 3
    [2] => 0
    [3] => 6
)
```
