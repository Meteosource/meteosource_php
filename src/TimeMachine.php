<?php

namespace Meteosource;

use DateTime;
use DateTimeZone;

/**
 * Represents archive weather data for specific location
 */
class TimeMachine
{

	private float $lat;
	private float $lon;
	private string $units;
	private int $elevation;
	private string $timezone;

	private MultipleTimesData $data;

	public function __construct(object $data, string $timezone)
	{
		$this->lat = $data->lat[-1] == 'N' ? (float) $data->lat : -(float)$data->lat;
		$this->lon = $data->lon[-1] == 'E' ? (float) $data->lon : -(float)$data->lon;
		$this->units = $data->units;
		$this->elevation = $data->elevation;
		$this->timezone = $timezone;

		if($this->timezone != 'UTC') {
			$data = $this->convertToTimeZone($data);
		}
		
		$this->data = isset($data->data) ? new MultipleTimesData($data, $timezone, 'TimeMachine') : null;
		
	}

	public function __toString(): string
	{
		return "<TimeMachine for lat: " . $this->lat . ", lon: " . $this->lon . ">\n";
	}

	public function __get(string $name)
	{
		return $this->$name;
	}

	/**
	 * Converts all DateTime like data to desired timezone
	 */
	private function convertToTimeZone($data)
	{
		$dateTimeFields = ['date', 'rise', 'set', 'onset', 'expires'];
		foreach($data as $key => &$part) {
			if(is_object($part) || is_array($part)) {
				$part = $this->convertToTimeZone($part);
			} else {
				if(in_array($key, $dateTimeFields)) {
					$dateTime = new DateTime($part, new DateTimeZone('UTC'));
					$dateTime->setTimezone(new DateTimeZone($this->timezone));
					$part = $dateTime->format('Y-m-d\TH:i:s');
				}	
			}
		}
		return $data;
	}
}
