<?php

namespace rdx\aggrel\PhpStan;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Properties\ModelProperty;
use Larastan\Larastan\Reflection\ReflectionHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use rdx\aggrel\HasAggregateTable;
use rdx\aggrel\HasManyScalar;

/**
 * @see Larastan\Larastan\Properties\ModelRelationsExtension
 */

class AggregateRelationExtension implements PropertiesClassReflectionExtension {

	public function hasProperty(ClassReflection $classReflection, string $propertyName) : bool {
		if (!$classReflection->isSubclassOf(Model::class)) {
			return false;
		}

		if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
			return false;
		}

		$hasNativeMethod = $classReflection->hasNativeMethod($propertyName);
		if (!$hasNativeMethod) {
			return false;
		}

		$returnType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod($propertyName)->getVariants())->getReturnType();
		if (
			(new ObjectType(HasAggregateTable::class))->isSuperTypeOf($returnType)->yes() ||
			(new ObjectType(HasManyScalar::class))->isSuperTypeOf($returnType)->yes()
		) {
			return true;
		}

		return false;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName) : PropertyReflection {
		$returnType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod($propertyName)->getVariants())->getReturnType();
		if ((new ObjectType(HasAggregateTable::class))->isSuperTypeOf($returnType)->yes()) {
			$type = new UnionType([
				new IntegerType(),
				new FloatType(),
			]);
		}
		else {
			$innerType = new UnionType([
				new IntegerType(),
				new FloatType(),
				new StringType(),
			]);
			$type = new ArrayType(new IntegerType(), $innerType);
		}

		return new ModelProperty($classReflection, $type, new NeverType(), false);
	}

}
