<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @template TRelatedModel of Model = Model
 * @template TDeclaringModel of Model = Model
 *
 * @extends HasAggregate<TRelatedModel, TDeclaringModel>
 */
class HasAggregateTable extends HasAggregate {

	public function __construct(QueryBuilder $query, Model $parent, string $foreignKey, ?string $localKey = null) {
		parent::__construct($query, $parent, $foreignKey, $localKey);
	}

}
