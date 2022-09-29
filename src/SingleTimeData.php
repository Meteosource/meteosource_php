<?php

namespace Meteosource;

/**
 * Represents data at specific one time
 * 
 * This class is used to represent 'current' section of forecast data, individual
 * timesteps of 'minutely', 'hourly', 'daily' also individual alerts and the timesteps of archive weather from TimeMachine
 */ 
class SingleTimeData
{
	private object $data;
	private string $timezone;

	public function __construct(object $data, string $timezone)
	{
		$this->timezone = $timezone;
		$this->data = $data;
	}

	public function __toString(): string
	{
		return "<Instance of SingleTimeData data with " . count((array)$this->data) . " member variables (" . implode(', ', array_keys((array)$this->data)) . ")>\n";
	}

	public function __get($name)
	{
		return $this->data->$name;
	}
}
