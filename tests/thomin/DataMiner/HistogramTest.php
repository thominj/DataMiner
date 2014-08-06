<?php

namespace thomin\DataMiner;

class HistogramTest extends \PHPUnit_Framework_TestCase 
{
	private $_bins;
	private $_data;
	
	public function setUp() 
	{
		$this->_bins = array(0, 1, 2, 3, 4, 5);
		$this->_data = $data = array(-1, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6);
		$this->_expected_result = array(
				'less' 	=> 1,
				0		=> 2,
				1		=> 2,
				2		=> 2,
				3		=> 2,
				4		=> 2,
				5		=> 3,
		); 
	}
	
	public function testSingleUse()
	{		
		$histogram = new Histogram($this->_bins, $this->_data);
		$result = $histogram->getResult();
		$this->assertEquals($this->_expected_result, $result);
	}
	
	public function testWithPreloadedResult()
	{
		$histogram = new Histogram($this->_bins);
		
		// Make a fake previous result with the correct keys (they have to match expected result keys)
		foreach ($this->_expected_result as $key => $value)
		{
			$previous_result[$key] = 1;
		}

		// The result after preloading should just be the previous result
		$histogram->preload($previous_result);
		$result = $histogram->getResult();
		$this->assertEquals($previous_result, $result);
		
		// If we add data, it should be added to the previous result
		$histogram->addData($this->_data);
		$result = $histogram->getResult();
		foreach ($this->_expected_result as $key => $value)
		{
			$expected_result[$key] = $previous_result[$key] + $value;
		}
		$this->assertEquals($expected_result, $result);
	}
}