<?php

namespace rdx\aggrel\PhpStan;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Properties\ModelProperty;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use RuntimeException;
use rdx\aggrel\HasAggregateTable;
use rdx\aggrel\HasManyScalar;
use rdx\aggrel\MultiColumnHasMany;

/**
 * @see Larastan\Larastan\Properties\ModelRelationsExtension
 */

final class AggregateRelationExtension implements PropertiesClassReflectionExtension {

	public function __construct(
		protected CollectionHelper $collectionHelper,
	) {}

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

		$returnType = $this->getReturnType($classReflection, $propertyName);
		if (
			$this->isClass($returnType, HasAggregateTable::class) ||
			$this->isClass($returnType, HasManyScalar::class) ||
			$this->isClass($returnType, MultiColumnHasMany::class)
		) {
			return true;
		}

		return false;
	}

	public function getProperty(ClassReflection $classReflection, string $propertyName) : PropertyReflection {
		$returnType = $this->getReturnType($classReflection, $propertyName);

		if ($this->isClass($returnType, HasAggregateTable::class)) {
			$type = new UnionType([
				new IntegerType(),
				new FloatType(),
			]);
		}
		elseif ($this->isClass($returnType, HasManyScalar::class)) {
			$innerType = new UnionType([
				new IntegerType(),
				new FloatType(),
				new StringType(),
			]);
			$type = new ArrayType(new IntegerType(), $innerType);
		}
		elseif ($this->isClass($returnType, MultiColumnHasMany::class)) {
			$modelType = $returnType->getClassReflection()->getActiveTemplateTypeMap()->getType('TRelatedModel');
			$type = $this->collectionHelper->determineCollectionClass($modelType->getClassName());
		}
		else {
			throw new RuntimeException("What happened??");
		}

		return new ModelProperty($classReflection, $type, new NeverType(), false);
	}

	protected function getReturnType(ClassReflection $classReflection, string $propertyName) : Type {
		return $classReflection->getNativeMethod($propertyName)->getVariants()[0]->getReturnType();
	}

	protected function isClass(Type $returnType, string $className) : bool {
		return (new ObjectType($className))->isSuperTypeOf($returnType)->yes();
	}

}
