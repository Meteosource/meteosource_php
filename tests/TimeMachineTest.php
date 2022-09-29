<?php
use PHPUnit\Framework\TestCase;


final class TimeMachineTest extends TestCase
{
    /**
     * @test
     */

    public function placeid_or_latlon_have_to_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No placeId or both lat and lon specified.');
        $meteosource->getTimeMachine('2022-07-01', null, null, null, null);
    }

    /**
     * @test
     */

    public function only_one_of_placeid_or_latlon_can_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('When placeId is specified, both lat and lon have to be null.');
        $meteosource->getTimeMachine('2022-07-01', null, null, 'los-angeles', '34.05', '-118.24');
    }

    /**
     * @test
     */
    public function date_or_date_range_have_to_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date or both DateFrom and DateTo have to specified.');
        $meteosource->getTimeMachine(null, null, null, null, null);
    }

    /**
     * @test
     */
    public function only_one_of_date_or_date_range_can_be_specified(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('When Date is specified both DateFrom and DateTo have to be null.');
        $meteosource->getTimeMachine('2022-06-10', '2022-06-05', '2022-06-12', 'london', null, null);
    }

    /**
     * @test
     */
    public function timemachine_attributes_loaded_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getTimeMachine')
             ->willReturn(new Meteosource\TimeMachine(json_decode(file_get_contents(__DIR__ . '/sampleTimeMachine.json')), 'UTC'));

        $timeMachine = $stubMeteosource->getTimeMachine('2021-01-01', null, null, 'london', null);

        $this->assertEquals('50.08804', $timeMachine->lat);
        $this->assertEquals('14.42076', $timeMachine->lon);
        $this->assertEquals(202, $timeMachine->elevation);
        $this->assertEquals('UTC', $timeMachine->timezone);
        $this->assertEquals('metric', $timeMachine->units);
    }

    /**
     * @test
     */
    public function timemachine_indexing(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');
        $timeMachine = $meteosource->getTimeMachine(['2020-10-01', '2020-10-02', '2020-11-03'], null, null, 'london', null);
        $this->assertEquals(72, count($timeMachine->data));

        $timeMachine = $meteosource->getTimeMachine(null, '2020-10-01', '2020-10-03', 'london', null);
        $this->assertEquals(72, count($timeMachine->data));

        $timeMachine = $meteosource->getTimeMachine(null, new DateTime('2020-10-01'), new DateTime('2020-10-03'), 'london', null);
        $this->assertEquals(72, count($timeMachine->data));
    }

    /**
     * @test
     */
    public function timemachine_one_of_dates_not_available(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');
        $this->expectOutputString("Problem with downloading 1980-10-10\n");
        $timeMachine = $meteosource->getTimeMachine(['2020-09-01', '1980-10-10', '2020-10-01'], null, null, 'london', null);
    }


    /**
     * @test
     */
    public function timemachine_daylight_saving_time(): void
    {
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');
        $timeMachine = $meteosource->getTimeMachine(['2021-10-30', '2021-10-31', '2021-11-01'], null, null, 'london', null, null, 'Europe/London');
        $this->assertEquals('2021-10-30T01:00:00', $timeMachine->data[0]->date);
        $this->assertEquals('2021-10-31T00:00:00', $timeMachine->data[23]->date);
        $this->assertEquals('2021-10-31T01:00:00', $timeMachine->data[24]->date);
        $this->assertEquals('2021-10-31T01:00:00', $timeMachine->data[25]->date);
        $this->assertEquals('2021-10-31T02:00:00', $timeMachine->data[26]->date);

        $timeMachine = $meteosource->getTimeMachine(['2021-03-27', '2021-03-28', '2021-03-29'], null, null, 'london', null, null, 'Europe/London');

        $this->assertEquals('2021-03-27T00:00:00', $timeMachine->data[0]->date);
        $this->assertEquals('2021-03-28T00:00:00', $timeMachine->data[24]->date);
        $this->assertEquals('2021-03-28T02:00:00', $timeMachine->data[25]->date);
        $this->assertEquals('2021-03-28T03:00:00', $timeMachine->data[26]->date);
    }
}