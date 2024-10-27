<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Staudenmeir\LaravelCte\DatabaseServiceProvider;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 *
 * @extends Relation<TRelatedModel, TDeclaringModel, Collection<int, TRelatedModel>>
 */
class MultiColumnHasMany extends Relation {

	protected const COL_PREFIX = '_mc_';
	protected const TABLE_NAME = '_mc';

	protected array $columns;
	protected array $columnAliases;

	public function __construct(EloquentBuilder $query, Model $parent, array $columns) {
		$this->related = $query->getModel();
		$this->query = $query;
		$this->parent = $parent;
		$this->columns = $columns;
		$this->columnAliases = array_map(fn(string $name) => self::COL_PREFIX . $name, $this->columns);
	}

	/**
	 * Set the constraints for a lazy load of the relation.
	 */
	public function addConstraints() {
		$table = $this->related->getTable();
		foreach ($this->columns as $localKey => $foreignKey) {
			$this->query->where("$table.$foreignKey", $this->parent->getAttribute($localKey));
		}
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 */
	public function addEagerConstraints(array $models) {
		if (class_exists(DatabaseServiceProvider::class)) {
			$this->addEagerConstraintsWithCte($models);
		}
		else {
			$this->addEagerConstraintsWithOrAnds($models);
		}
	}

	protected function addEagerConstraintsWithCte(array $models) {
		$rows = [];
		foreach ($models as $model) {
			$row = [];
			foreach ($this->columns as $localKey => $foreignKey) {
				$row[] = (int) $model->getAttribute($localKey);
			}
			$rows[] = $this->formatValuesRow($row);
		}
		$this->query->withExpression(self::TABLE_NAME, "VALUES " . implode(', ', array_unique($rows)), $this->columnAliases);

		$table = $this->related->getTable();
		$this->query->join(self::TABLE_NAME, function($join) use ($table) {
			foreach ($this->columns as $localKey => $foreignKey) {
				$join->on(self::TABLE_NAME . '.' . $this->columnAliases[$localKey], "$table.$foreignKey");
			}
		});
	}

	protected function addEagerConstraintsWithOrAnds(array $models) {
		$rows = [];
		$groups = [];
		foreach ($models as $model) {
			$row = [];
			foreach ($this->columns as $localKey => $foreignKey) {
				$id = (int) $model->getAttribute($localKey);
				$row[] = $id;
				$groups[$foreignKey][] = $id;
			}
			$rows[] = $row;
		}

		foreach ($groups as $key => $ids) {
			$this->query->whereIn($key, array_unique($ids));
		}

		$sql = '(' . implode(', ', $this->columns) . ') = (' . substr(str_repeat(', ?', count($this->columns)), 2) . ')';
		$this->query->where(function($query) use ($sql, $rows) {
			foreach ($rows as $ids) {
				$query->orWhereRaw($sql, $ids);
			}
		});
	}

	/**
	 * Get the relationship for eager loading.
	 */
	public function getEager() {
		if ($columns = $this->query->getQuery()->columns) {
			$this->query->addSelect(array_values($this->columns));
		}

		return $this->query->get($columns)->groupBy(function(Model $result) {
			return implode(', ', $result->only($this->columns));
		});
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 */
	public function match(array $models, Collection $results, $relation) {
		foreach ($models as $model) {
			$id = implode(', ', $model->only(array_keys($this->columns)));
			if (isset($results[$id])) {
				$model->$relation->__construct($results[$id]->all());
			}
		}

		return $models;
	}

	/**
	 * Get the results of the relationship.
	 */
	public function getResults() {
		$this->addConstraints();

		return $this->query->get();
	}

	/**
	 * Initialize the relation on a set of models.
	 */
	public function initRelation(array $models, $relation) {
		foreach ($models as $model) {
			$model->setRelation($relation, $this->related->newCollection());
		}

		return $models;
	}

	protected function formatValuesRow(array $row) : string {
		return 'ROW(' . implode(', ', $row) . ')';
	}

}
