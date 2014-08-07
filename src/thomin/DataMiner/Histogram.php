<?php

namespace thomin\DataMiner;

/**
 * Calculates the histogram of a dataset using either online or offline methods.
 * 
 * @author James Thomin <james.thomin@gmail.com>
 *
 */
class Histogram {
	
	private $_result = array();	// The result array
	private $_bins = array();	// The bins array
	private $_nbins = 0;		// The number of bins
	
	/**
	 * Constructor. The first (optional) argument is the bins array, and the second (optional)
	 * argument is a data set.
	 * 
	 * @param array $bins
	 * @param array $data
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
	 */
	public function setBins(array $bins)
	{
		sort($bins);	// Sort bins and re-key
		$this->_bins = $bins;
		$this->_nbins = count($bins);
		
		// Set up results array using bins as keys
		array_unshift($bins, 'less');	// prepend the 'less' bin
		$result = array_fill_keys($bins, 0);
		$this->_result = $result;
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Adds an array of data to the histogram. A bins array must be specified first.
	 * As the data is added, it is binned. To add data in online mode, simply call addData
	 * repeatedly with new datasets.
	 */
	public function addData(array $data)
	{
		if(empty($this->_bins)) throw new \Exception('A bins array must be set before adding data.');
		
		// Loop over data
		foreach($data as $x)
		{
			$bin = $this->_mapToBin($x);
			$this->_result[$bin]++;
		}
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Preloads a result array, for use in online mode.
	 * Any new histogram counts will be added to the preloaded results.
	 * The array keys of the preloaded array must match the values of the bins array.
	 */
	public function preload($result)
	{
		// @todo: check that array keys are correct
		$this->_result = $result;
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Returns the result array. Each key corresponds to the matching
	 * index in the bins array. Each value is the integer number of data
	 * points that fall within that bin.
	 * 
	 * @return array
	 */
	public function getResult()
	{
		return $this->_result;
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
				return $bin;
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
					$bin = $this->_bins[$i - 1];
				}
				return $bin;
			}
		}
	}
}