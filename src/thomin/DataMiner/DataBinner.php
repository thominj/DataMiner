<?php

namespace thomin\DataMiner;

/**
 * Sorts an array of ordered pairs (a, b) of data by sorting a into bins,
 * then finding the mean and variance of all b in that bin.
 * 
 * @author James Thomin <james.thomin@gmail.com>
 *
 */
class DataBinner {
	
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
	 * Sets the bins that will be used for the analysis. Note - since the
	 * bins are not dynamic, setting the bins also destroys any existing
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
		$result = array_fill_keys($bins, array('n' => 0, 'mean' => null, 's' => null));
		$this->_result = $result;
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Adds an array of data to the binner. A bins array must be specified first.
	 * As the data is added, it is binned. To add data in online mode, simply call addData
	 * repeatedly with new datasets.
	 * 
	 * @param array<array<x, y>>
	 */
	public function addData(array $data)
	{
		if(empty($this->_bins)) throw new \Exception('A bins array must be set before adding data.');

		foreach($data as $pair)
		{
			$x = $pair[0];
			$y = $pair[1];

			$bin = $this->_mapToBin($x);

			// Number of measurements in this bin
			$this->_result[$bin]['n']++;

			// Cumulative mean
			$old_mean = $this->_result[$bin]['mean'];
			$this->_result[$bin]['mean'] = $this->_newMean($old_mean, $y, $this->_result[$bin]['n']);

			// Cumulative S (used to find variance)
			$old_s = $this->_result[$bin]['s'];
			$this->_result[$bin]['s'] = $this->_newS($old_s, $old_mean, $this->_result[$bin]['mean'], $y);
		}
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Preloads a result array, for use in online mode.
	 * Any new data statistics will be accumulated with the preloaded results.
	 * The array keys of the preloaded array must match the values of the bins array.
	 * 
	 * Warning: this overwrites any existing results.
	 */
	public function preload($result_array)
	{
		// @todo: check that array keys are correct

		$this->_result = array();
		foreach($result_array as $bin => $result )
		{
			$this->_result[$bin]['mean'] = $result['mean'];
			$n = $this->_result[$bin]['n'] = $result['n'];
			
			// Convert the variance back to S
			$this->_result[$bin]['s'] = $result['var'] * ($n-1);
		}
	}

	//-----------------------------------------------------------------------------
	
	/**
	 * Returns the result array. Each key corresponds to the matching
	 * index in the bins array. Each value is an array with the mean,
	 * variance, and total number of data points in that bin.
	 * 
	 * @return array
	 */
	public function getResult()
	{
		foreach($this->_result as $bin => $result)
		{
			$new_result[$bin]['mean'] = $result['mean'];
			$new_result[$bin]['n'] = $result['n'];
			if($result['n'] - 1 == 0)
			{
				$new_result[$bin]['var'] = $result['s'];
			}
			else
			{
				$new_result[$bin]['var'] = $result['s'] / ($result['n'] - 1);
			}
		}
		return $new_result;
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
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Find the new mean using Welford's method (popularized by Knuth)
	 * It is (one of?) the most numerically stable algorithms.
	 * 
	 * @reference: http://www.johndcook.com/standard_deviation.html
	 */
	private function _newMean($old_mean, $x, $k)
	{
		// If there is no old_mean, then this is the first value, and it is equal to the mean.
		if($old_mean === null)
		{
			return $x;
		}
		else
		{
			return $old_mean + ($x - $old_mean) / $k;
		}
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Find the new variance using Welford's method (popularized by Knuth)
	 * It is (one of?) the most numerically stable algorithms.
	 * 
	 * @reference: http://www.johndcook.com/standard_deviation.html
	 */
	private function _newS($old_s, $old_mean, $current_mean, $x)
	{
		// If there is no old_mean, then this is the first value, and it is equal to the zero.
		if($old_mean === null)
		{
			return 0.0;
		}
		else
		{
			return $old_s + ($x - $old_mean) * ($x - $current_mean);
		}
	}
}