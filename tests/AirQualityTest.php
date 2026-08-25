<?php
use PHPUnit\Framework\TestCase;


final class AirQualityTest extends TestCase
{
    private function createMeteosource(): Meteosource\Meteosource
    {
        return new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY') ?: 'dummy-api-key', 'flexi');
    }

    /**
     * @test
     */

    public function placeid_or_latlon_have_to_be_specified(): void
    {
        $meteosource = $this->createMeteosource();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No placeId or both lat and lon specified.');
        $meteosource->getAirQuality(null, null, null);
    }

    /**
     * @test
     */

    public function only_one_of_placeid_or_latlon_can_be_specified(): void
    {
        $meteosource = $this->createMeteosource();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('When placeId is specified, both lat and lon have to be null.');
        $meteosource->getAirQuality('los-angeles', '34.05', '-118.24');
    }

    /**
     * @test
     */
    public function airquality_attributes_loaded_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getAirQuality')
             ->willReturn(new Meteosource\AirQuality(json_decode(file_get_contents(__DIR__ . '/sampleAirQuality.json')), 'UTC'));

        $airQuality = $stubMeteosource->getAirQuality('london', null, null);

        $this->assertEquals('51.50853', $airQuality->lat);
        $this->assertEquals('-0.12574', $airQuality->lon);
        $this->assertEquals(25, $airQuality->elevation);
        $this->assertEquals('UTC', $airQuality->timezone);
        $this->assertEquals(4, count($airQuality->data));
    }

    /**
     * @test
     */
    public function airquality_indexing(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getAirQuality')
             ->willReturn(new Meteosource\AirQuality(json_decode(file_get_contents(__DIR__ . '/sampleAirQuality.json')), 'UTC'));

        $airQuality = $stubMeteosource->getAirQuality('london', null, null);

        // Index 0 has to be accessible also by date string and DateTime
        $this->assertEquals(12.4, $airQuality->data[0]->pm10);
        $this->assertEquals(12.4, $airQuality->data['2022-06-01T00:00:00']->pm10);
        $this->assertEquals(12.4, $airQuality->data[new DateTime('2022-06-01T00:00:00')]->pm10);

        $this->assertEquals(3, $airQuality->data[2]->air_quality);
        $this->assertEquals(9.2, $airQuality->data['2022-06-01T02:00:00']->pm25);

        $this->expectException(OutOfBoundsException::class);
        $airQuality->data[1000];
    }

    /**
     * @test
     */
    public function airquality_timezones(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getAirQuality')
             ->willReturn(new Meteosource\AirQuality(json_decode(file_get_contents(__DIR__ . '/sampleAirQuality.json')), 'Asia/Kabul'));

        $airQuality = $stubMeteosource->getAirQuality('london', null, null, 'Asia/Kabul');

        $this->assertEquals('Asia/Kabul', $airQuality->timezone);
        // Equivalent to 2022-06-01T00:00:00 UTC
        $this->assertEquals(12.4, $airQuality->data['2022-06-01T04:30:00']->pm10);
    }
}
