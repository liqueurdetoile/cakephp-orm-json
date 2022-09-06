<?php
declare(strict_types=1);

namespace Lqdt\OrmJson\PHPStan;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

class DatFieldProperty implements PropertyReflection
{
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;

    /**
     * @var \PHPStan\Type\Type
     */
    private $type;

    /**
     * @param \PHPStan\Reflection\ClassReflection $declaringClass Owning class reflection
     */
    public function __construct(ClassReflection $declaringClass)
    {
        $this->declaringClass = $declaringClass;
        $this->type = new MixedType();
    }

    /**
     * @inheritDoc
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    /**
     * @inheritDoc
     */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isPrivate(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isPublic(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * @inheritDoc
     */
    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * @inheritDoc
     */
    public function getDocComment(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getReadableType(): Type
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getWritableType(): Type
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function canChangeTypeAfterAssignment(): bool
    {
        return true;
    }
}
