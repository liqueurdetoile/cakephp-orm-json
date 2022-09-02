<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\Fixture;

use Adbar\Dot;
use Faker\Factory;

/**
 * Brings up fluid scheme support in order to generate a fixed set of data for fixtures
 */
class DataGenerator
{
    /**
     * Faker generator instance
     *
     * @var \Faker\Generator
     */
    public $faker;

    /**
     * Scheme storage
     *
     * @var \Adbar\Dot
     */
    public $scheme;

    /**
     * Initializes trait and seeds faker instance to allow consistency generation between tests
     */
    public function __construct(int $seed = 0)
    {
        $this->faker = Factory::create();
        $this->scheme = new Dot();
        $this->seed($seed);
    }

    /**
     * Clears current scheme
     *
     * @return self
     */
    public function clear(): self
    {
        $this->scheme = new Dot();

        return $this;
    }

    /**
     * Seeds the faker
     *
     * @param  int $seed
     * @return self
     */
    public function seed(int $seed): self
    {
        $this->faker->seed($seed);

        return $this;
    }

    /**
     * Adds a static value
     *
     * @param  string $key Target key
     * @param  mixed $value Value
     * @return self
     */
    public function static(string $key, $value): self
    {
        $this->scheme->set($key, $value);

        return $this;
    }

    /**
     * Adds a callable to generate value
     *
     * Callback will be provided with three arguments :
     * - Current row data as Adbar\Dot
     * - Path key used to target value
     * - Row offset if multiple rows are requested
     *
     * @param  string   $key Target key
     * @param  callable $callable Callable
     * @return self
     */
    public function callable(string $key, callable $callable): self
    {
        $this->scheme->set($key, $callable);

        return $this;
    }

    /**
     * Adds a Faker generated value
     *
     * @param  string $key Target key
     * @param  string $fake Fake generator
     * @param  mixed  $params Additional params for generator
     * @return self
     * @see https://github.com/fzaninotto/Faker
     */
    public function faker(string $key, string $fake, ...$params): self
    {
        return $this->callable(
            $key,
            function () use ($fake, $params) {
                return $this->faker->$fake(...$params);
            }
        );
    }

    /**
     * Provides a sequence to the generator
     *
     * Data for each row will be fetched from the sequence at the same index
     * If there's more rows requested than available in sequence, sequence will be looped
     *
     * @param  string $key                    [description]
     * @param  array  $sequence               [description]
     * @return self             [description]
     */
    public function sequence(string $key, array $sequence): self
    {
        return $this->callable(
            $key,
            function ($data, $key, $offset) use ($sequence) {
                $n = count($sequence);
                $i = $offset % $n;

                return $sequence[$i];
            }
        );
    }

    /**
     * Generates data based on scheme description for a single entity or a collection
     *
     * When providing 1 as `$n`, it returns data that can be directly used to feed `newEntity` or `patchEntity` calls.
     *
     * If you want a collection of a single set of data, simply wrap result in array like this :  `[$this->generate()]`
     *
     * @param int $n Number of entities`
     * @return array
     */
    public function generate(int $n = 1, int $offset = 0): array
    {
        if ($n === 1) {
            $data = new Dot();
            foreach ($this->scheme->flatten() as $key => $value) {
                if (is_callable($value)) {
                    $data->set($key, $value($data, $key, $offset));
                } else {
                    $data->set($key, $value);
                }
            }

            return $data->all();
        }

        $collection = [];

        for ($i = 0; $i < $n; $i++) {
            $collection[] = $this->generate(1, $i);
        }

        return $collection;
    }
}
