<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\PHPStan;

use Lqdt\OrmJson\DatField\DatFieldParserTrait;
use Lqdt\OrmJson\DatField\Exception\UnparsableDatFieldException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
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

            return $classReflection->hasProperty($field);
        } catch (UnparsableDatFieldException $err) {
            return $classReflection->hasNativeProperty($propertyName);
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
        try {
            $field = $this->getDatFieldPart('field', $propertyName);

            if (!$classReflection->hasProperty($field)) {
                    /** @phpstan-ignore-next-line */
                    throw new MissingPropertyFromReflectionException($classReflection->getName(), $field);
            }

            return new DatFieldProperty($classReflection);
        } catch (UnparsableDatFieldException $err) {
            return $classReflection->getNativeProperty($propertyName);
        }
    }
}
