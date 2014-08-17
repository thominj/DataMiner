<?php

namespace thominj\DataMiner;

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
		// Remove the 'less' bin if it exists so it doesn't mess up sorting.
		$less_bin = array_search('less', $bins, TRUE);
		if($less_bin !== FALSE) unset($bins[$less_bin]);

		sort($bins);	// Sort bins and re-key
		$this->_bins = $bins;
		$this->_nbins = count($bins);

		// Set up results array
		$result = array_fill(0, $this->_nbins, array('n' => 0, 'mean' => null, 's' => null));
		$result['less'] = array('n' => 0, 'mean' => null, 's' => null);	// prepend the 'less' bin
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
	 * The array keys of the preloaded array will be used as the new bins array.
	 * 
	 * Warning: this overwrites any existing results.
	 */
	public function preload($result_array)
	{
		$bins = array_keys($result_array);
		$this->setBins($bins);

		$this->_result['less'] = $this->_convertFromDisplayResult($result_array['less']);
		for($i = 0; $i < $this->_nbins; ++$i)
		{
			$bin = (string)$this->_bins[$i];
			$this->_result[$i] = $this->_convertFromDisplayResult($result_array[$bin]);
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
		$new_result['less'] = $this->_convertToDisplayResult($this->_result['less']);

		for($i = 0; $i < $this->_nbins; ++$i)
		{
			$bin = (string)$this->_bins[$i];
			$new_result[$bin] = $this->_convertToDisplayResult($this->_result[$i]);
		}
		return $new_result;
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Converts the result to the display format (mostly we are just converting
	 * s to var)
	 * 
	 * @param array $result
	 * @return array $new_result
	 */
	private function _convertToDisplayResult($result)
	{
		// Mean and N are the same
		$new_result['mean'] = $result['mean'];
		$new_result['n'] = $result['n'];
		
		// Convert S to var
		if($result['n'] - 1 == 0)
		{
			$new_result['var'] = $result['s'];
		}
		else
		{
			$new_result['var'] = $result['s'] / ($result['n'] - 1);
		}
		return $new_result;
	}
	
	//-----------------------------------------------------------------------------
	
	private function _convertFromDisplayResult($result)
	{
		// Mean and N are the same
		$new_result['mean'] = $result['mean'];
		$new_result['n'] = $result['n'];
		
		// Convert var to S
		$new_result['s'] = $result['var'] * ($result['n'] - 1);

		return $new_result;
	}
	
	//-----------------------------------------------------------------------------
	
	/**
	 * Sort a value into the appropriate bin.
	 * 
	 * @param double $x
	 * @return int|string 'less' or a bin index
	 */
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
		// If there is no old_mean, then this is the first value, and it is equal to zero.
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