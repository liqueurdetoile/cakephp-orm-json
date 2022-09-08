<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\Test\PHPStan;

use Lqdt\OrmJson\PHPStan\CurlyDatFieldNotation;
use Lqdt\OrmJson\Test\Model\Entity\DatFieldEntity;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;

class CurlyDatFieldNotationTest extends PHPStanTestCase
{
    /**
     * @var \PHPStan\Broker\Broker
     */
    private $broker;

    /**
     * @var \Lqdt\OrmJson\PHPStan\CurlyDatFieldNotation
     */
    private $extension;

    protected function setUp(): void
    {
        $this->broker = $this->createBroker();
        $this->extension = new CurlyDatFieldNotation();
    }

    /**
     * @return mixed[]
     */
    public function dataHasProperty(): array
    {
        return [
            ['id', false],
            ['data', false],
            ['dato', false],
            ['test@data', true],
            ['data->test', true],
            ['test@id', false],
            ['id->test', false],
            ['test@dato', false],
            ['dato->test', false],
        ];
    }

    /**
     * @dataProvider dataHasProperty
     */
    public function testHasProperty(string $property, bool $result): void
    {
        $classReflection = $this->broker->getClass(DatFieldEntity::class);
        self::assertSame($result, $this->extension->hasProperty($classReflection, $property));
    }

    public function testGetProperty(): void
    {
        $classReflection = $this->broker->getClass(DatFieldEntity::class);
        $propertyReflection = $this->extension->getProperty($classReflection, 'data->test');
        self::assertSame($classReflection, $propertyReflection->getDeclaringClass());
        self::assertFalse($propertyReflection->isStatic());
        self::assertFalse($propertyReflection->isPrivate());
        self::assertTrue($propertyReflection->isPublic());
        self::assertSame('mixed', $propertyReflection->getReadableType()->describe(VerbosityLevel::value()));
    }
}
