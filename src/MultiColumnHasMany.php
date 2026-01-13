<?php

namespace rdx\aggrel;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
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

	public function __construct(EloquentBuilder $query, Model $parent, array $columns) {
		$this->related = $query->getModel();
		$this->query = $query;
		$this->parent = $parent;
		$this->columns = $columns;
	}

	/**
	 * Set the constraints for a lazy load of the relation.
	 */
	public function addConstraints() {
		$table = $this->related->getTable();
		foreach ($this->columns as $localKey => $foreignKey) {
			$foreignColumn = str_contains($foreignKey, '.') ? $foreignKey : "$table.$foreignKey";
			$this->query->where($foreignColumn, $this->parent->getAttribute($localKey));
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
		$columnAliases = array_map(fn(string $name) => self::COL_PREFIX . Arr::last(explode('.', $name)), $this->columns);

		$rows = [];
		foreach ($models as $model) {
			$row = [];
			foreach ($this->columns as $localKey => $foreignKey) {
				$row[] = (int) $model->getAttribute($localKey);
			}
			$rows[] = $this->formatValuesRow($row);
		}
		$this->query->withExpression(self::TABLE_NAME, "VALUES " . implode(', ', array_unique($rows)), $columnAliases);

		$table = $this->related->getTable();
		$this->query->join(self::TABLE_NAME, function($join) use ($table, $columnAliases) {
			foreach ($this->columns as $localKey => $foreignKey) {
				$foreignColumn = str_contains($foreignKey, '.') ? $foreignKey : "$table.$foreignKey";
				$join->on(self::TABLE_NAME . '.' . $columnAliases[$localKey], $foreignColumn);
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

		$onlyKeys = array_values(array_map(fn(string $name) => Arr::last(explode('.', $name)), $this->columns));
		return $this->query
			->get($columns)
			->groupBy(function(Model $result) use ($onlyKeys) {
				return implode(':', $result->only($onlyKeys));
			});
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 */
	public function match(array $models, Collection $results, $relation) {
		$onlyKeys = array_keys($this->columns);
		foreach ($models as $model) {
			$index = implode(':', $model->only($onlyKeys));
			if (isset($results[$index])) {
				$collection = $model->$relation;
				foreach ($results[$index] as $object) {
					$collection[] = $object;
				}
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
