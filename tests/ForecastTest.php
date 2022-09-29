<?php
use PHPUnit\Framework\TestCase;


final class ForecastTest extends TestCase
{
    /**
     * @test
     */

    public function placeid_or_latlon_have_to_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No placeId or both lat and lon specified.');
        $meteosource->getPointForecast(null, null, null, null, null);
    }

    /**
     * @test
     */

    public function only_one_of_placeid_or_latlon_can_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('When placeId is specified, both lat and lon have to be null.');
        $meteosource->getPointForecast('los-angeles', '34.05', '-118.24', null, null, null);
    }

    /**
     * @test
     */
    public function forecast_attributes_loaded_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getPointForecast')
             ->willReturn(new Meteosource\Forecast(json_decode(file_get_contents(__DIR__ . '/sampleForecast.json')), 'UTC'));

        $pointForecast = $stubMeteosource->getPointForecast(null, null, null, null, null);

        $this->assertEquals('51.50853', $pointForecast->lat);
        $this->assertEquals('-0.12574', $pointForecast->lon);
        $this->assertEquals(25, $pointForecast->elevation);
        $this->assertEquals('UTC', $pointForecast->timezone);
        $this->assertEquals('metric', $pointForecast->units);
    }

    /**
     * @test
     */
    public function forecast_indexing(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getPointForecast')
             ->willReturn(new Meteosource\Forecast(json_decode(file_get_contents(__DIR__ . '/sampleForecast.json')), 'UTC'));

        $pointForecast = $stubMeteosource->getPointForecast('london', null, null, null, null);

        $this->assertEquals(106, $pointForecast->hourly[1]->wind->angle);
        $this->assertEquals(23.2, $pointForecast->hourly['2021-09-08T11:00:00']->feels_like);
        $this->assertEquals(23.2, $pointForecast->hourly[new DateTime('2021-09-08T11:00:00')]->feels_like);

        // Test different timezone (UTC - 5)
        $this->assertEquals(23.2, $pointForecast->hourly[new DateTime('2021-09-08T06:00:00', new DateTimeZone('America/Lima'))]->feels_like);

        $this->expectException(OutOfBoundsException::class);
        $pointForecast->hourly[1000];

        $this->expectException(OutOfBoundsException::class);
        $pointForecast->hourly['foo'];

        $this->expectException(OutOfBoundsException::class);
        $pointForecast->hourly[new DateTime('2022-07-07T10:00:00')];
    }

    /**
     * @test
     */
    public function forecast_timezones(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getPointForecast')
             ->willReturn(new Meteosource\Forecast(json_decode(file_get_contents(__DIR__ . '/sampleForecast.json')), 'Asia/Kabul'));

        $pointForecast = $stubMeteosource->getPointForecast('london', null, null, null, 'Asia/Kabul');
        
        $this->assertEquals('Asia/Kabul', $pointForecast->timezone);

        $this->assertEquals(106, $pointForecast->hourly[1]->wind->angle);
        // Equivalent to 2021-09-08T11:00:00 UTC
        $this->assertEquals(23.2, $pointForecast->hourly['2021-09-08T15:30:00']->feels_like);
        $this->assertEquals(23.2, $pointForecast->hourly[new DateTime('2021-09-08T11:00:00', new DateTimeZone('UTC'))]->feels_like);
    }

    /**
     * @test
     */
    public function alerts(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getPointForecast')
             ->willReturn(new Meteosource\Forecast(json_decode(file_get_contents(__DIR__ . '/sampleForecast.json')), 'UTC'));

        $pointForecast = $stubMeteosource->getPointForecast('london', null, null, null, null);
        
        $this->assertEquals('Moderate Thunderstorms', $pointForecast->alerts[3]->event);
        $this->assertEquals(3, count($pointForecast->alerts->getActive('2022-03-08T22:10:00')));
        $this->assertEquals(3, count($pointForecast->alerts->getActive(new DateTime('2022-03-08T23:00:00'))));

        $this->assertEquals(2, count($pointForecast->alerts->getActive(new DateTime('2022-03-08T23:00:00', new DateTimeZone('Asia/Bangkok')))));
    }


}