<?php
namespace Chartbeat;

/*
 * Library to interact with Chartbeat API
 * 
 * @see http://api.chartbeat.com
 * @author Eero Niemi <eero@eero.info>
 */
class Chartbeat
{
	/**
	 * @var string Chartbeat API key
	 */
	private $apiKey;

	/**
	 * @var string Chartbeat host
	 */
	private $host;

	/**
	 * @var string Chartbeat API base access URL
	 */
	private $baseUrl = 'http://api.chartbeat.com';

	/*
	 * @var string Timeout for requests
	 */
	private $timeout = 5; // 5 seconds 

	/*
	 * @var string CURL object
	 */
	private $curl = null;

	/**
	 * Creates API interaction object
	 *
	 * You can also set $apiKey and $host via setApiKey()
	 * and setHost() methods.
	 *
	 * @param string $host    Your Chartbeat host
	 * @param string $apiKey  Your Chartbeat API key
	 */
	public function __construct($host = null, $apiKey = null)
	{
		if ($host !== null)
		{
			$this->setHost($host);
		}

		if ($apiKey !== null)
		{
			$this->setApiKey($apiKey);
		}
		

		if (!function_exists('curl_init'))
		{
			throw new Exception('CURL extension is required!');
		}
	}
	
	/*
	 * Initialize CURL object
	 *
	 * @todo Better way to configure curl
	 * @todo Exception handling (timeout, 403, etc) 
	 */
	private function initCurl()
	{
		if ($this->curl === null)
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$this->curl = $curl;
		}
	}
	
	/**
	 * Set Chartbeat API key
	 *
	 * @param string $apiKey  Chartbeat API key
	 */
	public function setApiKey($apiKey)
	{
		$this->apiKey = $apiKey;
	}
	
	/**
	 * Set Chartbeat host
	 *
	 * @param string $host  Chartbeat host
	 */
	public function setHost($host)
	{
		$this->host = $host;
	}

	/**
	 * Set Chartbeat base URL
	 *
	 * @param string $apiKey  Chartbeat base URL
	 */
	
	public function setBaseUrl($baseUrl)
	{
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Make request to Chartbeat HTTP API with Curl
	 *
	 * @param  string $url URL
	 * @return array       JSON decoded data got from Chartbeat
	 */
	private function makeRequest($url)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		$data_json = curl_exec($this->curl);
		$data = json_decode($data_json);
		$xferInfo = curl_getinfo($this->curl);

		if (isset($data->error))
		{
			$msgStr = isset($data->error->message) ? ' Message: ' . $data->error->message : '';
			$codeStr = isset($data->error->code) ? ', Code: ' . $data->error->code : '';
			if (empty($msgStr) && empty($codeStr))
			{
				$msgStr = ' Unknown error';
			}
			throw new Exception('Chartbeat API error.' . $msgStr . $codeStr, Exception::ERROR_CHARTBEAT_API);
		}

		if ($xferInfo['http_code'] != 200)
		{
			throw new Exception('Curl error, invalid HTTP response code ' . $xferInfo['http_code'], Exception::ERROR_HTTP_RETURN_CODE);
		}

		$curlErrorNumber = curl_error($this->curl);
		if ($curlErrorNumber != 0)
		{
			$curlErrorMsg = curl_error($this->curl);
			throw new Exception('Curl error ' . $curlErrorMsg . ' / ' . $curlErrorNumber, Exception::ERROR_CURL);
		}
		curl_close($this->curl);
		$this->curl = null; // delete cURL object after every request;
		return $data;
	}

	/*
	 * Fetch data from Chartbeat API
	 *
	 * $path examples:
	 *  historical/traffic/stats/, live/toppages/v3/
	 *
	 * $userParams is array containing key-value pairs of options 
	 * you want to pass with request.
	 * Chartbeat API explorer is great place to see what parameters 
	 * are available
	 *
	 * @see http://chartbeat.com/docs/api/explore/
	 * 
	 * @param string    $path       API path
	 * @param array     $userParams Parameters passed to API
	 */
	public function get($path, $userParams = array())
	{
		$this->initCurl();
		$baseParams = array ('apikey' => $this->apiKey, 'host' => $this->host);
		$params = array_merge($baseParams, $userParams);
		$queryStr = http_build_query($params);
		$url = $this->baseUrl . '/' . $path . '?' . $queryStr;
		$cbData = $this->makeRequest($url);
		return $cbData;
	}

	/**
	 * Get monthly max people (=concurrent users on site)
	 *
	 * @param  array    $userParams     Extra parameters passed to API
	 * @return integer                  Monthly max people 
	 */
	public function getMonthlyMaxPeople($userParams = array())
	{
		$cbData = $this->get('historical/traffic/stats/', $userParams);
		$max = isset($cbData->data->{$this->host}->people->max) ? $cbData->data->{$this->host}->people->max : null;
		return $max;
	}

	/*
	 * Get maximum amount of people between certain timestamps
	 *
	 * About timestamp-parameters: A string in the form of a unix timestamp, 
	 * YYYY-mm-dd, YY-mm-dd HH:MM:SS or a time delta, where the time delta 
	 * specified is start time prior to now. 
	 *
	 * NOTE: start/end is only accepted in EST.
	 *
	 * @param mixed  $start         Start time
	 * @param mixed  $end           End time
	 * @param array  $userParams    Extra parameters passed to API
	 * @return       integer        Max people
	 */
	public function getMaxPeopleBetween($start, $end, $userParams = array())
	{
		$userParams = array_merge($userParams, array('start' => $start, 'end' => $end, 'properties' => 'max'));
		$cbData = $this->get('historical/traffic/stats/', $userParams);
		$max = isset($cbData->data->{$this->host}->people->max) ? $cbData->data->{$this->host}->people->max : null;
		return $max;
	}

	/*
	 * Get minimum amount of people between certain timestamps
	 *
	 * About timestamp-parameters: A string in the form of a unix timestamp, 
	 * YYYY-mm-dd, YY-mm-dd HH:MM:SS or a time delta, where the time delta 
	 * specified is start time prior to now. 
	 *
	 * NOTE: start/end is only accepted in EST.
	 *
	 * @param mixed  $start         Start time
	 * @param mixed  $end           End time
	 * @param array  $userParams    Extra parameters passed to API
	 * @return       integer        Max people
	 */
	public function getMinPeopleBetween($start, $end, $userParams = array())
	{
		$userParams = array_merge($userParams, array('start' => $start, 'end' => $end, 'properties' => 'min'));
		$cbData = $this->get('historical/traffic/stats/', $userParams);
		$min = isset($cbData->data->{$this->host}->people->min) ? $cbData->data->{$this->host}->people->min : null;
		return $min;
	}
	
	/*
	 * Get Facebook likes between certain timestamps
	 *
	 * See getMinPeopleBetween() or getMaxPeopleBetween() to more information about
	 * how to use timestamp-parameters ($start and $end)
	 *
	 * @param mixed  $start         Start time
	 * @param mixed  $end           End time
	 * @param array  $userParams    Extra parameters passed to API
	 * @return       integer        Number of Facebook likes
	 */
	public function getFacebookLikesBetween($start, $end, $userParams = array())
	{
		$userParams = array_merge($userParams, array('start' => $start, 'end' => $end, 'fields' => 'fb_page_likes', 'properties' => 'max'));
		$cbData = $this->get('historical/social/stats/', $userParams);
		$likes = isset($cbData->data->{$this->host}->fb_page_likes->max) ? $cbData->data->{$this->host}->fb_page_likes->max : null;
		return $likes;
	}
}