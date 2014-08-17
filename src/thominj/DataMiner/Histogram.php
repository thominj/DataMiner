<?php

namespace thominj\DataMiner;

/**
 * Calculates the histogram of a dataset using either online or offline methods.
 * 
 * @author James Thomin <james.thomin@gmail.com>
 *
 */
class Histogram {
	
	private $_result = array();	// The result array
	private $_less = 0;		// The number of data points that were less than the minimum
	private $_bins = array();	// The bins array
	private $_nbins = 0;		// The number of bins
	
	/**
	 * Constructor. If bins and data are passed as arguments, it will
	 * process the data.
	 * 
	 * @param array $bins - (optional) array of bins
	 * @param array $data - (optional) array of data
	 */
	public function __construct(array $bins = array(), array $data = array() )
	{
		if( ! empty($bins)) $this->setBins($bins);
		if( ! empty($data)) $this->addData($data);
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Sets the bins that will be used for the histogram. Note - since histogram
	 * bins are not dynamic, setting the bins also destroys any existing histogram
	 * results. 
	 * 
	 * Bins are defined so that they are left-inclusive, 
	 * which means that each bin value represents the left edge of the bin.
	 * For bins b_i … b_n, the result array will have keys:
	 *
	 *	Less: x < b_0
	 *	b_0: b_0 <= x < b_1
	 *	b_i: b_i <= x < b_i+1
	 *	…
	 *	b_N: b_N <= x < infinity
	 *
	 *	@param array $bins - the array of bins, left-inclusive, with the last bin representing the right
	 *                       boundary of the last bin
	 */
	public function setBins(array $bins)
	{
		// Remove the 'less' bin if it exists so it doesn't mess up sorting.
		$less_bin = array_search('less', $bins, TRUE);
		if($less_bin !== FALSE) unset($bins[$less_bin]);
		
		// Convert bins to float if necessary so we aren't doing casting
		// during processing
		foreach($bins as &$bin)
		{
			$bin = (float)$bin;
		}
		sort($bins);	// Sort bins and re-key
		$this->_bins = $bins;
		$this->_nbins = count($bins);
		
		// Set up results array and empty less counter
		$result = array_fill(0, $this->_nbins, 0);
		$this->_result = $result;
		$this->_less = 0;
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Adds an array of data to the histogram. A bins array must be specified first.
	 * As the data is added, it is binned. To add data in online mode, simply call addData
	 * repeatedly with new datasets.
	 * 
	 * @param array $data - the data array. Keys are ignored.
	 */
	public function addData(array $data)
	{
		if(empty($this->_bins)) throw new \Exception('A bins array must be set before adding data.');

		foreach($data as $x)
		{
			$bin = $this->_mapToBin($x);
			if($bin === 'less')
			{
				$this->_less++;
			}
			else
			{
				$this->_result[$bin]++;
			}
		}
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Preloads a result array, for use in online mode.
	 * This overwrites any results that already exists, and uses the 
	 * array keys to define the new bins.
	 * 
	 * @param array $result - an array of a previous result.
	 */
	public function preload($result)
	{
		$bins = array_keys($result);
		$this->setBins($bins);
		$this->_less = $result['less'];
		unset($result['less']);
		$this->_result = array_values($result);
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Returns the result array.
	 * 
	 * @return array $result - The result of the binning operation. Keys are bins,
	 *                         and values are the data counts for each bin.
	 */
	public function getResult()
	{
		$result['less'] = $this->_less;
		for($i = 0; $i < $this->_nbins; ++$i)
		{
			$result[(string)$this->_bins[$i]] = $this->_result[$i];
		}
		return $result;
	}
	
	//-----------------------------------------------------------------------------
	
	private function _mapToBin($x)
	{
		// Loop over bins array
		for($i = 0; $i < $this->_nbins; ++$i)
		{
			// Get this bin's left endpoint
			$bin = $this->_bins[$i];
	
			// If this is the last bin, and we still haven't found a bin larger than x, then store it here
			if($i == $this->_nbins - 1 && $x >= $bin)
			{
				return $i;
			}
			// Otherwise, if x is less than the left endpoint,
			else if ($x < $bin)
			{
				// and this is the first bin, we have to store it in the "less" bin
				if($i == 0)
				{
					$bin = 'less';
				}
				// otherwise, it should be stored in the previous bin
				else
				{
					$bin = $i - 1;
				}
				return $bin;
			}
		}
	}
}