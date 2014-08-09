<?php

namespace thomin\DataMiner;

class DataBinnerTest extends \PHPUnit_Framework_TestCase 
{
	private $_bins;
	private $_data_set_1;
	private $_expected_result_data_set_1;
	private $_data_set_2;
	private $_expected_result_data_set_2;
	private $_expected_result_data_set_1_and_2;
	private $_tolerance = 0.0000000001;
	
	public function setUp() 
	{
		$this->_bins = array(0, 1, 2, 3, 4, 5);
		$this->_data_set_1 = [
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
		$this->_data_set_2 = [
				[-1, 0],
				[0, 1],
				[0.5, 2],
				[1, 1],
				[1.5, 2],
				[2, 1],
				[2.5, 2],
				[3, 1],
				[3.5, 2],
				[4, 1],
				[4.5, 2],
				[5, 1],
				[5.5, 2],
				[6, 1]
			];
		$this->_expected_result_data_set_1 = array(
				'less' 	=> array('mean' => -1, 'var' => 0, 'n' => 1),
				0		=> array('mean' => 0.25, 'var' => 0.125, 'n' => 2),
				1		=> array('mean' => 1.25, 'var' => 0.125, 'n' => 2),
				2		=> array('mean' => 2.25, 'var' => 0.125, 'n' => 2),
				3		=> array('mean' => 3.25, 'var' => 0.125, 'n' => 2),
				4		=> array('mean' => 4.25, 'var' => 0.125, 'n' => 2),
				5		=> array('mean' => 5.5, 'var' => 0.25, 'n' => 3)
		);
		$this->_expected_result_data_set_2 = array(
				'less' 	=> array('mean' => 0, 'var' => 0, 'n' => 1),
				0		=> array('mean' => 1.5, 'var' => 0.5, 'n' => 2),
				1		=> array('mean' => 1.5, 'var' => 0.5, 'n' => 2),
				2		=> array('mean' => 1.5, 'var' => 0.5, 'n' => 2),
				3		=> array('mean' => 1.5, 'var' => 0.5, 'n' => 2),
				4		=> array('mean' => 1.5, 'var' => 0.5, 'n' => 2),
				5		=> array('mean' => 1.5, 'var' => 0.3333333333, 'n' => 3)
		);
		$this->_expected_result_data_set_1_and_2 = array(
				'less' 	=> array('mean' => -0.5, 'var' => 0.5, 'n' => 2),
				0		=> array('mean' => 0.875, 'var' => 0.7291666667, 'n' => 4),
				1		=> array('mean' => 1.375, 'var' => 0.2291666667, 'n' => 4),
				2		=> array('mean' => 1.875, 'var' => 0.3958333333, 'n' => 4),
				3		=> array('mean' => 2.375, 'var' => 1.2291666667, 'n' => 4),
				4		=> array('mean' => 2.875, 'var' => 2.7291666667, 'n' => 4),
				5		=> array('mean' => 3.4166666667, 'var' => 5.4416666667, 'n' => 6)
		);
	}
	
	public function testSingleUse()
	{
		$dataBinner = new DataBinner($this->_bins, $this->_data_set_1);
		$result = $dataBinner->getResult();
		$this->_checkResults($this->_expected_result_data_set_1, $result);
	}
	
	public function testWithPreloadedResult()
	{
		// No need to set bins since preloaded result has them
		$dataBinner = new DataBinner();
		$dataBinner->preload($this->_expected_result_data_set_1);
		$dataBinner->addData($this->_data_set_2);
		$result = $dataBinner->getResult();
		$this->_checkResults($this->_expected_result_data_set_1_and_2, $result);
	}
	
	public function testOnlineMode()
	{
		$dataBinner = new DataBinner($this->_bins);
		$dataBinner->addData($this->_data_set_1);
		$dataBinner->addData($this->_data_set_2);
		$result = $dataBinner->getResult();
		$this->_checkResults($this->_expected_result_data_set_1_and_2, $result);
		
		// The order shouldn't matter
		$dataBinner = new DataBinner($this->_bins);
		$dataBinner->addData($this->_data_set_2);
		$dataBinner->addData($this->_data_set_1);
		$result = $dataBinner->getResult();
		$this->_checkResults($this->_expected_result_data_set_1_and_2, $result);
	}
	
	private function _checkResults($expected_result_array, $result_array)
	{
		foreach($expected_result_array as $bin => $expected_result)
		{
			$result = $result_array[$bin];
			$this->assertEquals($expected_result['mean'], $result['mean'], "Bin: $bin", $this->_tolerance);
			$this->assertEquals($expected_result['var'], $result['var'], "Bin: $bin", $this->_tolerance);
			$this->assertEquals($expected_result['n'], $result['n']);
		}
	}
}