<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

class HasAggregate extends HasOneOrMany {

	protected $aggregate = '1';

	public function aggregate($raw) {
		$this->aggregate = $raw;

		return $this;
	}

	protected function makeAggregateSelect() {
		return "$this->aggregate as x";
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 */
	public function addEagerConstraints(array $models) {
		$aggregate = $this->makeAggregateSelect();

		$this->query->whereIn($this->foreignKey, $this->getKeys($models, $this->localKey));
		$this->query->groupBy($this->foreignKey);
		$this->select($this->foreignKey);
		$this->selectRaw($aggregate);
	}

	/**
	 * Get the relationship for eager loading.
	 */
	public function getEager() {
		return new Collection($this->pluck('x', $this->foreignKey));
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 */
	public function match(array $models, Collection $results, $relation) {
		foreach ($models as $model) {
			$id = $model->getAttribute($this->localKey);
			if (isset($results[$id])) {
				$model->setRelation($relation, $results[$id]);
			}
		}

		return $models;
	}

	/**
	 * Get the results of the relationship.
	 */
	public function getResults() {
		$aggregate = $this->makeAggregateSelect();
		return $this->query->selectRaw($aggregate)->value('x');
	}

	/**
	 * Initialize the relation on a set of models.
	 */
	public function initRelation(array $models, $relation) {
		foreach ($models as $model) {
			$model->setRelation($relation, 0);
		}

		return $models;
	}

}
