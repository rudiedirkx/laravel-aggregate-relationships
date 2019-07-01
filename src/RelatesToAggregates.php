<?php

namespace rdx\aggrel;

use Illuminate\Database\Query\Builder as QueryBuilder;

trait RelatesToAggregates {

	protected function hasAggregate($related, $aggregate, $foreignKey, $localKey = null) {
		$instance = new $related();

		$localKey = $localKey ?: $this->getKeyName();

		return (new HasAggregate($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey))
			->aggregate($aggregate);
	}

	protected function hasCount($related, $foreignKey, $localKey = null) {
		return $this->hasAggregate($related, 'count(1)', $foreignKey, $localKey)
			->default(0);
	}

	protected function hasManyScalar($targetKey, $targetTable, $foreignKey, $localKey = null) {
		$conn = $this->getConnection();
		$grammar = $conn->getQueryGrammar();
		$query = new QueryBuilder($conn, $grammar, $conn->getPostProcessor());

		return new HasManyScalar($query->from($targetTable), $this, $targetKey, $foreignKey, $localKey);
	}

}
