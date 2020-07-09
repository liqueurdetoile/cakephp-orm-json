<?php
namespace Lqdt\OrmJson\Test\Fixture;

use Adbar\Dot;
use Faker\Factory;

class DataGenerator
{
    public $faker;
    public $scheme;

    public function __construct()
    {
        $this->faker = Factory::create();
        $this->scheme = new Dot();
    }

    public function seed($seed) : self
    {
        $this->faker->seed($seed);

        return $this;
    }

    public function add(string $key, $value) : self
    {
        $this->scheme->set($key, $value);

        return $this;
    }

    public function addCallable(string $key, callable $callable) : self
    {
        $this->scheme->set($key, $callable);

        return $this;
    }

    public function addFaker(string $key, string $fake, ...$params) : self
    {
        return $this->addCallable($key, function () use ($fake, $params) {
            return $this->faker->$fake(...$params);
        });
    }

    public function addCopy(string $key, string $sourcekey) : self
    {
        return $this->addCallable($key, function (Dot $data) use ($sourcekey) {
            return $data->get($sourcekey);
        });
    }

    public function addRandomRelations(string $foreignKey, array $collection, string $bindingKey = 'id') : self
    {
        return $this->addCallable($foreignKey, function () use ($collection, $bindingKey) {
            $data = $this->faker->randomElement($collection);
            $data = new Dot($data);
            return $data->get($bindingKey);
        });
    }

    public function addUniqueRelation(string $foreignKey, array $collection, string $bindingKey = 'id') : self
    {
        $unused = [];
        for ($i = 0; $i < count($collection); $i++) {
            $unused[] = $i;
        }

        return $this->addCallable($foreignKey, function () use ($collection, $bindingKey, &$unused) {
            $index = $this->faker->randomElement($unused);
            if ($index === null) {
                return null;
            }
            array_splice($unused, array_search($index, $unused), 1);
            $data = new Dot($collection[$index]);
            return $data->get($bindingKey);
        });
    }

    public function generate(int $n = 1)
    {
        if ($n === 1) {
            $data = new Dot();
            foreach ($this->scheme->flatten() as $key => $value) {
                if (is_callable($value)) {
                    $data->set($key, $value($data, $key));
                } else {
                    $data->set($key, $value);
                }
            }

            return $data->all();
        }


        $collection = [];

        for ($i = 0; $i < $n; $i++) {
            $collection[] = $this->generate(1);
        }

        return $collection;
    }
}
