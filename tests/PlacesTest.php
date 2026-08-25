<?php
use PHPUnit\Framework\TestCase;


final class PlacesTest extends TestCase
{
    private function skipWithoutApiKey(): void
    {
        if(getenv('METEOSOURCE_API_KEY') === false) {
            $this->markTestSkipped('METEOSOURCE_API_KEY environment variable is not set.');
        }
    }

    /**
     * @test
     */
    public function find_places_parsed_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('findPlaces')
             ->willReturn(json_decode(file_get_contents(__DIR__ . '/samplePlaces.json')));

        $places = $stubMeteosource->findPlaces('london');

        $this->assertIsArray($places);
        $this->assertEquals(3, count($places));
        $this->assertEquals('london', $places[0]->place_id);
        $this->assertEquals('United Kingdom', $places[0]->country);
        $this->assertEquals('Europe/London', $places[0]->timezone);
        $this->assertEquals('london-6058560', $places[1]->place_id);
        $this->assertEquals('Canada', $places[1]->country);
    }

    /**
     * @test
     */
    public function find_places_prefix_parsed_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('findPlacesPrefix')
             ->willReturn(json_decode(file_get_contents(__DIR__ . '/samplePlaces.json')));

        $places = $stubMeteosource->findPlacesPrefix('lond');

        $this->assertIsArray($places);
        $this->assertEquals('London', $places[0]->name);
        $this->assertEquals('settlement', $places[0]->type);
    }

    /**
     * @test
     */
    public function nearest_place_parsed_correctly(): void
    {
        $stubMeteosource = $this->createStub(Meteosource\Meteosource::class);
        $stubMeteosource->method('getNearestPlace')
             ->willReturn(json_decode(file_get_contents(__DIR__ . '/sampleNearestPlace.json')));

        $place = $stubMeteosource->getNearestPlace(51.50853, -0.12574);

        $this->assertIsObject($place);
        $this->assertEquals('London', $place->name);
        $this->assertEquals('london', $place->place_id);
        $this->assertEquals('England', $place->adm_area1);
        $this->assertEquals('Europe/London', $place->timezone);
    }

    /**
     * @test
     */
    public function nearest_place_live(): void
    {
        $this->skipWithoutApiKey();
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $place = $meteosource->getNearestPlace(51.50853, -0.12574);
        $this->assertEquals('london', $place->place_id);
    }

    /**
     * @test
     */
    public function find_places_live(): void
    {
        $this->skipWithoutApiKey();
        $meteosource = new Meteosource\Meteosource(getenv('METEOSOURCE_API_KEY'), 'flexi');

        $places = $meteosource->findPlaces('london');
        $this->assertNotEmpty($places);
        $this->assertEquals('London', $places[0]->name);
    }
}
