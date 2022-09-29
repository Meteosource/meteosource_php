<?php

namespace Meteosource;

use DateTime;
use DateTimeZone;

/**
 * Represents forecast for one specific location (point)
 */
class Forecast
{
	private float $lat;
	private float $lon;
	private string $units;
	private int $elevation;
	private string $timezone;

	private ?SingleTimeData $current;
	private ?MultipleTimesData $minutely;
	private ?MultipleTimesData $hourly;
	private ?MultipleTimesData $daily;
	private ?AlertsData $alerts;
	

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

		$this->current = isset($data->current) ? new SingleTimeData($data->current, $timezone) : null;
		$this->daily = isset($data->daily) ? new MultipleTimesData($data->daily, $timezone, 'Daily') : null;
		$this->hourly = isset($data->hourly) ? new MultipleTimesData($data->hourly, $timezone, 'Hourly') : null;
		$this->minutely = isset($data->minutely) ? new MultipleTimesData($data->minutely, $timezone, 'Minutely') : null;
		$this->alerts = isset($data->alerts) ? new AlertsData($data->alerts, $timezone, 'Alerts') : null;
	}

	public function __toString(): string
	{
		return "<Forecast for lat: " . $this->lat . ", lon: " . $this->lon . ">\n";
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
