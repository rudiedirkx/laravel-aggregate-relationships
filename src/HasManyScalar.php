<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends Relation<TRelatedModel, TDeclaringModel, Collection<int, scalar>>
 */
class HasManyScalar extends Relation {

	protected ?string $targetKeyKey;
	protected string $targetValueKey;
	protected string $foreignKey;
	protected string $localKey;

	public function __construct(QueryBuilder $query, Model $parent, string $targetKey, string $foreignKey, ?string $localKey = null) {
		$this->query = $query;
		$this->parent = $parent;
		$this->targetKeyKey = $this->targetValueKey = $targetKey;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey ?: $parent->getKeyName();
	}

	public function resultKey(?string $key) {
		$this->targetKeyKey = $key;

		return $this;
	}

	public function resultValue(string $key) {
		$this->targetValueKey = $key;

		return $this;
	}

	/**
	 * Set the constraints for a lazy load of the relation.
	 */
	public function addConstraints() {
		$this->query->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey));
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 */
	public function addEagerConstraints(array $models) {
		$this->query->whereIn($this->foreignKey, array_column($models, $this->localKey));
		$this->select(array_unique([$this->foreignKey, $this->targetKeyKey, $this->targetValueKey]));
	}

	/**
	 * Get the relationship for eager loading.
	 */
	public function getEager() {
		return (new Collection($this->query->get()))->groupBy($this->foreignKey);
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 */
	public function match(array $models, Collection $results, $relation) {
		foreach ($models as $model) {
			$id = $model->getAttribute($this->localKey);
			if (isset($results[$id])) {
				$model->setRelation($relation, $results[$id]->pluck($this->targetValueKey, $this->targetKeyKey)->all());
			}
		}

		return $models;
	}

	/**
	 * Get the results of the relationship.
	 */
	public function getResults() {
		$this->addConstraints();

		return $this->query->pluck($this->targetValueKey, $this->targetKeyKey)->all();
	}

	/**
	 * Initialize the relation on a set of models.
	 */
	public function initRelation(array $models, $relation) {
		foreach ($models as $model) {
			$model->setRelation($relation, []);
		}

		return $models;
	}

}
