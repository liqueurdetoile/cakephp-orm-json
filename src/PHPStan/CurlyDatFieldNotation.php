<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\PHPStan;

use Lqdt\OrmJson\DatField\DatFieldParserTrait;
use Lqdt\OrmJson\DatField\Exception\UnparsableDatFieldException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;

class CurlyDatFieldNotation implements PropertiesClassReflectionExtension
{
    use DatFieldParserTrait;

    /**
     * Check that json field owning datfield exists in class
     *
     * @param \PHPStan\Reflection\ClassReflection $classReflection Class reflection
     * @param  string          $propertyName      Datfield
     * @return bool
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        try {
            $field = $this->getDatFieldPart('field', $propertyName);

            if (!$classReflection->hasProperty($field)) {
                return false;
            }

            $property = $classReflection->getProperty($field, new \PHPStan\Analyser\OutOfClassScope());

            return get_class($property->getReadableType()) === 'PHPStan\Type\ArrayType';
        } catch (UnparsableDatFieldException $err) {
            return false;
        }
    }

    /**
     * Returns property reflection for datfield
     *
     * @param \PHPStan\Reflection\ClassReflection $classReflection Class reflection
     * @param  string             $propertyName                  Property name
     * @return \PHPStan\Reflection\PropertyReflection
     * @throws \PHPStan\Reflection\MissingPropertyFromReflectionException
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        return new DatFieldProperty($classReflection);
    }
}
