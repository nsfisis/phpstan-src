<?php

declare(strict_types=1);

namespace PHPStan\Type\Accessory;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\ObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class HasPropertyValueType implements AccessoryType, CompoundType
{

	use ObjectTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(private string $propertyName, private Type $valueType)
	{
	}

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function getPropertyName(): string
	{
		return $this->propertyName;
	}

	public function getValueType(): Type
	{
		return $this->valueType;
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->acceptsWithReason($type, $strictTypes)->result;
	}

	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof CompoundType) {
			return $type->isAcceptedWithReasonBy($this, $strictTypes);
		}

		return AcceptsResult::createFromBoolean($this->equals($type));
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($this->equals($type)) {
			return TrinaryLogic::createYes();
		}
		return TrinaryLogic::createMaybe();
		// return $type->hasProperty($this->propertyName)
		// 	->and($this->valueType->isSuperTypeOf($type->TODO($this->propertyName)));
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}
		return TrinaryLogic::createMaybe();

		// if ($otherType instanceof self) {
		// 	$limit = TrinaryLogic::createYes();
		// } else {
		// 	$limit = TrinaryLogic::createMaybe();
		// }
		//
		// return $limit->and($otherType->hasProperty($this->propertyName))
		// 	->and($this->valueType->isSuperTypeOf($type->TODO($this->propertyName)));
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isAcceptedWithReasonBy($acceptingType, $strictTypes)->result;
	}

	public function isAcceptedWithReasonBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return new AcceptsResult($this->isSubTypeOf($acceptingType), []);
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $this->propertyName === $type->propertyName
			&& $this->valueType->equals($type->valueType);
	}

	public function describe(VerbosityLevel $level): string
	{
		return sprintf('hasPropertyValue(%s, %s)', $this->propertyName, $this->valueType->describe($level));
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		if ($this->propertyName === $propertyName) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [new TrivialParametersAcceptor()];
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function traverse(callable $cb): Type
	{
		$newValueType = $cb($this->valueType);
		if ($newValueType === $this->valueType) {
			return $this;
		}

		return new self($this->propertyName, $newValueType);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		$newValueType = $cb($this->valueType, $right->TODO($this->offsetType));
		if ($newValueType === $this->valueType) {
			return $this;
		}

		return new self($this->propertyName, $newValueType);
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public static function __set_state(array $properties): Type
	{
		return new self($properties['propertyName'], $properties['valueType']);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode(''); // no PHPDoc representation
	}

}
