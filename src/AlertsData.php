<?php

namespace Meteosource;

use DateTime;

/**
 * Represents alerts data
 */

class AlertsData extends Data
{

	public function __construct(object $data, string $timezone, string $type = 'Alerts')
	{
		$this->timezone = $timezone;
		$this->type = $type;
		foreach($data->data as $alert) {
			$this->data[] = new SingleTimeData($alert, $timezone);
		}
	}

	public function __toString(): string
	{
		return "<" . $this->type ." data with " . count((array) $this->data) . " alerts>\n";
	}

	/** 
	 * Get all alerts active on requested date
	 * 
	 * @param string|DateTime $data
	 * @return array $activeAlerts array of SingleTimeData representing active alerts
	 */
	public function getActive($date = null): ?array
	{
		if($date === null) {
			$date = new DateTime();
		}
		if (is_string($date)) {
			$date = new DateTime($date);
		}

		$activeAlerts = [];

		foreach ($this->data as $alert) {
			if (new DateTime($alert->onset) <= $date && new DateTime($alert->expires) >= $date) {
				$activeAlerts[] = $alert;
			}
		}
		return $activeAlerts;
	}

	public function offsetGet($offset)
    {
        return $this->data[$offset];
    }
}
