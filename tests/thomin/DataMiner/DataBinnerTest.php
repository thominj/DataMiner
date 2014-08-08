<?php

namespace thomin\DataMiner;

class DataBinnerTest extends \PHPUnit_Framework_TestCase 
{
	private $_bins;
	private $_data;
	private $_expected_result;
	private $_tolerance = 0.0000000001;
	
	public function setUp() 
	{
		$this->_bins = array(0, 1, 2, 3, 4, 5);
		$this->_data = [
				[-1, -1],
				[0, 0],
				[0.5, 0.5],
				[1, 1],
				[1.5, 1.5],
				[2, 2],
				[2.5, 2.5],
				[3, 3],
				[3.5, 3.5],
				[4, 4],
				[4.5, 4.5],
				[5, 5],
				[5.5, 5.5],
				[6, 6]
			];
		$this->_expected_result = array(
				'less' 	=> array('mean' => -1, 'var' => 0, 'n' => 1),
				0		=> array('mean' => 0.25, 'var' => 0.125, 'n' => 2),
				1		=> array('mean' => 1.25, 'var' => 0.125, 'n' => 2),
				2		=> array('mean' => 2.25, 'var' => 0.125, 'n' => 2),
				3		=> array('mean' => 3.25, 'var' => 0.125, 'n' => 2),
				4		=> array('mean' => 4.25, 'var' => 0.125, 'n' => 2),
				5		=> array('mean' => 5.5, 'var' => 0.25, 'n' => 3)
		);
	}
	
	public function testSingleUse()
	{
		$dataBinner = new DataBinner($this->_bins, $this->_data);
		$result_array = $dataBinner->getResult();
		$n = count($this->_expected_result);
		foreach($this->_expected_result as $bin => $expected_result)
		{
			$result = $result_array[$bin];
			$this->assertEquals($expected_result['mean'], $result['mean'], "Bin: $bin", $this->_tolerance);
			$this->assertEquals($expected_result['var'], $result['var'], "Bin: $bin", $this->_tolerance);
			$this->assertEquals($expected_result['n'], $result['n']);
		}
	}
	
	public function testWithPreloadedResult()
	{

	}
	
	public function testOnlineMode()
	{

	}
}