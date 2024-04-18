<?php

namespace rdx\aggrel\PhpStan;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\Larastan\Properties\ModelProperty;
use NunoMaduro\Larastan\Reflection\ReflectionHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use rdx\aggrel\HasAggregateTable;

/**
 * @see NunoMaduro\Larastan\Properties\ModelRelationsExtension
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
		if (!(new ObjectType(HasAggregateTable::class))->isSuperTypeOf($returnType)->yes()) {
			return false;
		}

		return true;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName) : PropertyReflection {
		$type = new UnionType([
			new IntegerType(),
			new FloatType(),
			// new NullType(),
		]);
		return new ModelProperty($classReflection, $type, new NeverType(), false);
	}

}
