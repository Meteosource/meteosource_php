<?php

namespace Meteosource;

use DateTime;
use DateInterval;
use InvalidArgumentException;
use Exception;

/**
 * Main class that provides interface for Meteosource API.
 * To use this class you have to obtain your own API key at https://www.meteosource.com/pricing
 * 
 * Class provides methods to fetch:
 *  - weather forecast data for any place or coordinates - getPointForecast() 
 *  - archive weather data for any place or coordinates - getTimeMachine() 
 */
class Meteosource
{
    const API_VERSION = 'v1';

    private string $apiKey;
    private string $tier;
    private string $host;

    /**
     * 
     * @param string $apiKey Meteosouce API key
     * @param string $tier Meteosource tier 
     * @param string $host
     */ 
    public function __construct(string $apiKey, string $tier, string $host = 'https://www.meteosource.com')
    {
        $this->apiKey = $apiKey;
        $this->tier = $tier;
        $this->host = $host;
    }

    /**
     * Get forecast data for given place
     * Parameteres correspond to API point endpoint parameters documented at https://www.meteosource.com/documentation#point
     * 
     * @param string $placeId Identifier of place
     * @param float $lat Latitude in format 12N, 12.3N, 12.3, or 13S, 13.2S, -13.4
     * @param float $lon Longitude in format 12E, 12.3E, 12.3, or 13W, 13.2W, -13.4
     * @param array $sections Sections to be included in the result
     * @param string $timezone Timezone to be used for the date fields. Default is "UTC"
     * @param string $lang Language of text summaries. Default is "en"
     * @param string $units Unit system to be used. Posible values: auto, metric, us, uk, ca
     * @return Forecast
     * 
     * @throws InvalidArgumentException
     */

    public function getPointForecast(?string $placeId = null, ?float $lat = null, ?float $lon = null , ?array $sections = ['current', 'hourly'],
    ?string $timezone = 'UTC', ?string $lang = 'en', ?string $units = 'auto'): Forecast
    {
        if($placeId === null && ($lat === null || $lon === null)) {
            throw new InvalidArgumentException('No placeId or both lat and lon specified.');
        }
        if($placeId !== null && ($lat !== null || $lon !== null)) {
            throw new InvalidArgumentException('When placeId is specified, both lat and lon have to be null.');   
        }
        $requestParams = [
                        'place_id' => $placeId,
                        'lat' => $lat,
                        'lon' => $lon,
                        'sections' => implode(',', $sections),
                        'units' => $units,
                        'language' => $lang,
                        'timezone' => 'UTC',
                        ];

        $url = $this->buildUrl('point', $requestParams);
        $data = $this->getData($url);
        return new Forecast($data, $timezone);
    }


    /**
     * Get archive data for given place
     * Parameters correspond to time_machine API endpoint parameters documented at https://www.meteosource.com/documentation#time_machine
     * 
     * @param string|DateTime $date Date or array of dates to be fetched. Have to be null if dateFrom and dateTo are specified
     * @param string|DateTime $dateFrom Starting date of date range
     * @param string|DateTime $dateTo Ending date of date range
     * @param string $placeId Identifier of place
     * @param float $lat Latitude in format 12N, 12.3N, 12.3, or 13S, 13.2S, -13.4
     * @param float $lon Longitude in format 12E, 12.3E, 12.3, or 13W, 13.2W, -13.4
     * @param string $timezone Timezone to be used for the date fields. Default is "UTC"
     * @param string $units Unit system to be used. Posible values: auto, metric, us, uk, ca
     * @param string $callback Name of user defined function that takes one parameter, currently fetched date and is called after data for this date are fetched
     * @return TimeMachine
     * 
     * @throws InvalidArgumentException
     */


    public function getTimeMachine($date = null, $dateFrom = null, $dateTo = null, ?string $placeId = null, ?float $lat = null, ?float $lon = null, string $timezone = 'UTC', $units = 'auto', $callback = null): TimeMachine
    {
        if($date === null && ($dateFrom === null || $dateTo === null)) {
            throw new InvalidArgumentException('Date or both DateFrom and DateTo have to specified.');
        }

        if($date !== null && ($dateFrom !== null || $dateTo !== null)) {
            throw new InvalidArgumentException('When Date is specified both DateFrom and DateTo have to be null.');
        }

        if($placeId === null && ($lat === null || $lon === null)) {
            throw new InvalidArgumentException('No placeId or both lat and lon specified.');
        }
        if($placeId !== null && ($lat !== null || $lon !== null)) {
            throw new InvalidArgumentException('When placeId is specified, both lat and lon have to be null.');   
        }

        $datesToFetch = [];
        
        if($date !== null) {
            if($date instanceof DateTime) {
                $date = $date->format('Y-m-d');
            }
            if(is_array($date)) {
                foreach($date as $oneDay) {
                    if($oneDay instanceof DateTime) {
                        $oneDay = $oneDay->format('Y-m-d');
                    }
                    $datesToFetch[] = $oneDay;
                }
            } else {
                $datesToFetch[] = $date;    
            }
        } else {
            if(is_string($dateFrom)) {
                $dateFrom = new DateTime($dateFrom);
            }
            if(is_string($dateTo)) {
                $dateTo = new DateTime($dateTo);
            }

            while($dateFrom <= $dateTo) {
                $datesToFetch[] = $dateFrom->format('Y-m-d');
                $dateFrom->add(new DateInterval('P1D'));
            }
        }
        $timeMachineData = null;

        foreach($datesToFetch as $date) {
            $requestParams = [
                            'place_id' => $placeId,
                            'lat' => $lat,
                            'lon' => $lon,
                            'units' => $units,
                            'date' => $date,
                            'timezone' => 'UTC',
                            ];
            $url = $this->buildUrl('time_machine', $requestParams);
            try {
                $data = $this->getData($url);
            } catch (Exception $ex) {
                echo "Problem with downloading " . $date . "\n";
            }

            if(!$timeMachineData) {
                $timeMachineData = $data;
            } else {
                $timeMachineData->data = array_merge($timeMachineData->data, $data->data);
            }

            if($callback !== null)
                call_user_func($callback, $date);
        }
        
        return new TimeMachine($timeMachineData, $timezone);
    }

    private function buildUrl(string $endpoint, array $data): string
    {
        $data['key'] = $this->apiKey;
        return $this->host . "/api/" . self::API_VERSION . "/" . $this->tier . "/" . $endpoint . "?" . http_build_query($data);
    }

    private function getData(string $url): ?object
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($httpCode == 200)
            return json_decode($data);

        if(substr((string)$httpCode, 0, 1) == '4' || substr((string)$httpCode, 0, 1) == '5')
            throw new Exception('Server returned ' . $httpCode . " with message " . $data);
    }
}
