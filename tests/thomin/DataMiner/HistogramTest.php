<?php

namespace thomin\DataMiner;

class HistogramTest extends \PHPUnit_Framework_TestCase 
{
	private $_bins;
	private $_data;
	private $_expected_result;
	
	public function setUp() 
	{
		$this->_bins = array(0, 1, 2, 3, 4, 5);
		$this->_data = array(-1, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6);
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

	//-----------------------------------------------------------------------------	
	
	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage A bins array must be set before adding data.
	 */
	public function testNoBins()
	{
		$histogram = new Histogram();
		$histogram->addData($this->_data);
		$result = $histogram->getResult();
	}

	//-----------------------------------------------------------------------------	
	
	public function testSingleUse()
	{		
		$histogram = new Histogram($this->_bins, $this->_data);
		$result = $histogram->getResult();
		$this->assertEquals($this->_expected_result, $result);
	}

	//-----------------------------------------------------------------------------	
	
	public function testWithPreloadedResult()
	{
		// We don't need to set bins, since the preloaded result will have them.
		$histogram = new Histogram();
		
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

	//-----------------------------------------------------------------------------

	public function testOnlineMode()
	{
		$histogram = new Histogram($this->_bins, $this->_data);
		$histogram->addData($this->_data);
		foreach($this->_expected_result as $key => $value)
		{
			$expected_result[$key] = $value * 2;
		}
		$result = $histogram->getResult();
		$this->assertEquals($expected_result, $result);
	}
}