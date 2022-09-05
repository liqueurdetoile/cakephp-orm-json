<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\TestCase\RealCase;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class GeolocationTest extends TestCase
{
    public $Assignments;
    public $Drivers;
    public $Vehicles;
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

        $this->Assignments = TableRegistry::get('Assignments', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\AssignmentsTable',
        ]);

        $this->Drivers = TableRegistry::get('Drivers', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\DriversTable',
        ]);

        $this->Vehicles = TableRegistry::get('Vehicles', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\VehiclesTable',
        ]);

        $this->Locations = TableRegistry::get('Locations', [
          'className' => '\Lqdt\OrmJson\Test\Model\Table\LocationsTable',
        ]);

        // $generator = new DataGenerator();
        //
        // $drivers = $generator
        //   ->seed(0)
        //   ->faker('id', 'uuid')
        //   ->faker('name', 'name')
        //   ->generate(5);
        //
        // $vehicles = $generator
        //   ->clear()
        //   ->faker('id', 'uuid')
        //   ->callable('geocode_id', function ($data, $key, $offset) {
        //     return '42' . (string)$offset . 'EF';
        //   })
        //   ->generate(8);
        //
        // $assignments = $generator
        //   ->clear()
        //   ->faker('id', 'uuid')
        //   ->faker('driver_id', 'randomElement', array_map(function ($driver) {
        //     return $driver['id'];
        //   }, $drivers))
        //   ->faker('vehicle_id', 'randomElement', array_map(function ($vehicle) {
        //     return $vehicle['id'];
        //   }, $vehicles))
        //   ->faker('beginning', 'numberBetween', 1657700000, 1657799999)
        //   ->callable('ending', function ($data) {
        //     return (int)$data->get('beginning') + 3600;
        //   })
        //   ->generate(50);
        //
        // $locations = $generator
        //   ->clear()
        //   ->faker('timestamp', 'numberBetween', 1657700000, 1657799999)
        //   ->faker('position.lat', 'randomFloat', 5, 45.5, 47.5)
        //   ->faker('position.lon', 'randomFloat', 5, 4.5, 4.6)
        //   ->faker('vehicle.id', 'randomElement', array_map(function ($vehicle) {
        //     return $vehicle['geocode_id'];
        //   }, $vehicles))
        //   ->generate(500);
        //
        // $this->Drivers->saveManyOrFail($this->Drivers->newEntities($drivers));
        // $this->Vehicles->saveManyOrFail($this->Vehicles->newEntities($vehicles));
        // $this->Assignments->saveManyOrFail($this->Assignments->newEntities($assignments));
        //
        // // Manually saving locations as there's no primary key
        // foreach ($locations as $location) {
        //     $statement = $this->Locations
        //       ->query()
        //       ->insert(['data'])
        //       ->values(['data' => $location])
        //       ->execute();
        //
        //     if (!$statement->rowCount()) {
        //         throw new \Exception();
        //     }
        //
        //     $statement->closeCursor();
        // }
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
     * Checks that we can easily select all locations for a given vehicle with timestamp
     */
    public function testSelectingDatFieldInLocations(): void
    {
        $vehicles = $this->Vehicles
          ->find()
          ->where(['geocode_id' => '421EF'])
          ->contain('Locations', function ($q) {
              return $q->select([
                'Locations.data->vehicle.id',
                'timestamp' => 'Locations.data->timestamp',
              ]);
          })
          ->all();

        $this->assertEquals(1, $vehicles->count());
        $vehicle = $vehicles->first();
        $this->assertEquals(84, count($vehicle->locations));
        foreach ($vehicle->locations as $location) {
            $this->assertEquals($vehicle->geocode_id, $location->data_vehicle_id);
            $this->assertNotEmpty($location->timestamp);
        }
    }

    /**
     * Here we're fetching which vehicules where in a given are a in a given period
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
     * Only one was found !
     */
    public function testWhoWasThere(): void
    {
        $from = FrozenTime::createFromTimestamp(1657755000);
        $to = FrozenTime::createFromTimestamp(1657760000);

        $q = $this->Drivers
          ->useDatfields()
          ->find()
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

        $this->assertEquals('421EF', $driver->_matchingData['Vehicles']['geocode_id']);
    }
}
