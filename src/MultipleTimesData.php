<?php

namespace Meteosource;

use DateTime;
use DateTimeZone;

/**
 * Represents multiple time steps of data
 * 
 * This class is used to represent sections 'minutely', 'hourly', 'daily' and TimeMachine data
 * 
 */
class MultipleTimesData extends Data
{
	public function __construct(object $data, string $timezone, $type = null)
	{
		$this->timezone = $timezone;
		$this->type = $type;
		$dateAttributeName = $this->type == 'Daily' ? 'day' : 'date';
		foreach($data->data as $hour) {			
			$this->data[] = new SingleTimeData($hour, $timezone);
			$this->datesStr[] = $hour->$dateAttributeName;
			$this->datesDateTime[] = new DateTime($hour->$dateAttributeName, new DateTimeZone($timezone));
		}
	}

	public function __toString(): string
	{
		return "<" . $this->type ." data with " . count($this->data) . " timesteps from " . $this->datesStr[0] . " to " . end($this->datesStr) . ">\n";
	}
}
