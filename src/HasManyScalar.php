<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class HasManyScalar extends Relation {

	protected $targetKey;
	protected $foreignKey;
	protected $localKey;

	public function __construct(QueryBuilder $query, Model $parent, $targetKey, $foreignKey, $localKey = null) {
		$this->query = $query;
		$this->parent = $parent;
		$this->targetKey = $targetKey;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey ?: $parent->getKeyName();
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
		$this->select([$this->foreignKey, $this->targetKey]);
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
				$model->setRelation($relation, $results[$id]->pluck($this->targetKey, $this->targetKey)->all());
			}
		}

		return $models;
	}

	/**
	 * Get the results of the relationship.
	 */
	public function getResults() {
		$this->addConstraints();
		return $this->query->pluck($this->targetKey, $this->targetKey)->all();
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
