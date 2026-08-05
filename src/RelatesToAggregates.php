<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;

trait RelatesToAggregates {

	protected function hasAggregateTable(string $table, string $aggregate, string $foreignKey, ?string $localKey = null) : HasAggregateTable {
		$query = $this->getConnection()->table($table);
		return (new HasAggregateTable($query, $this, $foreignKey, $localKey))
			->aggregate($aggregate);
	}

	/**
	 * @template TRelated of Model
	 * @param class-string<TRelated> $related
	 * @return HasAggregateEloquent<TRelated, $this>
	 */
	protected function hasAggregate(string $related, string $aggregate, string $foreignKey, ?string $localKey = null) : HasAggregateEloquent {
		$query = (new $related())->newModelQuery();

		return (new HasAggregateEloquent($query, $this, $foreignKey, $localKey))
			->aggregate($aggregate);
	}

	/**
	 * @template TRelated of Model
	 * @param class-string<TRelated> $related
	 * @return HasAggregateEloquent<TRelated, $this>
	 */
	protected function hasCount(string $related, string $foreignKey, ?string $localKey = null) : HasAggregateEloquent {
		return $this->hasAggregate($related, 'count(1)', $foreignKey, $localKey)
			->default(0);
	}

	protected function hasCountTable(string $table, string $foreignKey, ?string $localKey = null) : HasAggregateTable {
		return $this->hasAggregateTable($table, 'count(1)', $foreignKey, $localKey)
			->default(0);
	}

	protected function hasManyScalar(string $targetKey, string $targetTable, string $foreignKey, ?string $localKey = null) : HasManyScalar {
		$query = $this->getConnection()->table($targetTable);
		return new HasManyScalar($query, $this, $targetKey, $foreignKey, $localKey);
	}

	/**
	 * @param array<string, string> $columns
	 */
	protected function multiColumnHasMany(string $related, array $columns) : MultiColumnHasMany {
		$instance = $this->newRelatedInstance($related);
		return new MultiColumnHasMany($instance->newQuery(), $this, $columns);
	}

}
