<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends HasAggregate<TRelatedModel, TDeclaringModel>
 */
class HasAggregateEloquent extends HasAggregate {

	public function __construct(EloquentBuilder $query, Model $parent, string $foreignKey, ?string $localKey = null) {
		parent::__construct($query, $parent, $foreignKey, $localKey);
	}

}
