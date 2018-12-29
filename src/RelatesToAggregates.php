<?php

namespace rdx\aggrel;

trait RelatesToAggregates {

	protected function hasAggregate($related, $aggregate, $foreignKey, $localKey = null) {
		$instance = $this->newRelatedInstance($related);

		$localKey = $localKey ?: $this->getKeyName();

		return (new HasAggregate($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey))
			->aggregate($aggregate);
	}

	protected function hasCount($related, $foreignKey, $localKey = null) {
		return $this->hasAggregate($related, 'count(1)', $foreignKey, $localKey)
			->default(0);
	}

}
