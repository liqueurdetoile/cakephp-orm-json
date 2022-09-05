<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\RealCase;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * In this test, we have a bunch of geolocation data into a separate noSQL database about vehicles and their drivers
 * Police has found out that one of our vehicle have been seen near a crime scene
 * We wants to find out which vehicle it was ans who was driving it
 * Bulk geolocation data is simply imported into a Mysql Table without more processing
 */
class GeolocationTest extends TestCase
{
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\AssignmentsTable
     */
    public $Assignments;
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\DriversTable
     */
    public $Drivers;
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\VehiclesTable
     */
    public $Vehicles;
    /**
     * @var \Lqdt\OrmJson\Test\Model\Table\LocationsTable
     */
    public $Locations;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $connection = ConnectionManager::get('test');
        $connection->execute('TRUNCATE drivers');
        $connection->execute('TRUNCATE vehicles');
        $connection->execute('TRUNCATE drivers_vehicles');
        $connection->execute('TRUNCATE locations');
        $import = file_get_contents(FIXTURES . 'geolocation_data.sql');
        $connection->execute($import);
    }

    public function setUp(): void
    {
        parent::setUp();

        /** @phpstan-ignore-next-line */
        $this->Assignments = TableRegistry::get('Assignments', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\AssignmentsTable',
        ]);

        /** @phpstan-ignore-next-line */
        $this->Drivers = TableRegistry::get('Drivers', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\DriversTable',
        ]);

        /** @phpstan-ignore-next-line */
        $this->Vehicles = TableRegistry::get('Vehicles', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\VehiclesTable',
        ]);

        /** @phpstan-ignore-next-line */
        $this->Locations = TableRegistry::get('Locations', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\LocationsTable',
        ]);
    }

    public function tearDown(): void
    {
        unset($this->Assignments);
        unset($this->Drivers);
        unset($this->Locations);
        unset($this->Vehicles);

        TableRegistry::clear();
        parent::tearDown();
    }

    /**
     * Here we're fetching which vehicules where in a given are a in a given period with the driver
     *
     * It turns out that it was 421EF and 427EF ones but only 421EF has an active driver at this time
     */
    public function testWhatWasThere(): void
    {
        $from = FrozenTime::createFromTimestamp(1657755000);
        $to = FrozenTime::createFromTimestamp(1657760000);

        $q = $this->Vehicles
          ->find('datfields')
          ->order(['geocode_id'])
          ->contain('Drivers', function ($q) use ($from, $to) {
              return $q->where([
                'OR' => [
                  $q->newExpr()->between('Assignments.beginning', $from, $to),
                  $q->newExpr()->between('Assignments.ending', $from, $to),
                ],
              ]);
          })
          ->innerJoinWith('Locations', function ($q) use ($from, $to) {
              return $q->where([
                $q->newExpr()->between('Locations.data->timestamp', $from->timestamp, $to->timestamp),
                $q->newExpr()->between('Locations.data->position.lat', 46.4, 46.6),
                $q->newExpr()->between('Locations.data->position.lon', 4.53, 4.56),
              ]);
          });

        $this->assertEquals(2, $q->count());
        $this->assertSame(['421EF', '427EF'], $q->all()->extract('geocode_id')->toArray(false));
    }

    /**
     * Here, we're directly fetching drivers who are on duty on vehicles located in a given area in a given period
     *
     * Only one was found ! Prof. Reece Legros IV is clearly a suspect !
     */
    public function testWhoWasThere(): void
    {
        $from = FrozenTime::createFromTimestamp(1657755000);
        $to = FrozenTime::createFromTimestamp(1657760000);

        $q = $this->Drivers
          ->find('json')
          ->distinct()
          ->matching('Vehicles.Locations', function ($q) use ($from, $to) {
              return $q->where([
                $q->newExpr()->between('Locations.data->timestamp', $from->timestamp, $to->timestamp),
                $q->newExpr()->between('Locations.data->position.lat', 46.4, 46.6),
                $q->newExpr()->between('Locations.data->position.lon', 4.53, 4.56),
              ]);
          })
          ->matching('Assignments', function ($q) use ($from, $to) {
              return $q->where([
                'OR' => [
                  $q->newExpr()->between('Assignments.beginning', $from, $to),
                  $q->newExpr()->between('Assignments.ending', $from, $to),
                ],
              ]);
          });

        $this->assertEquals(1, $q->count());
        $driver = $q->first();

        /** @phpstan-ignore-next-line */
        $this->assertEquals('421EF', $driver->_matchingData['Vehicles']['geocode_id']);
    }
}
