<?php namespace Chestnut\Foundation\Database;

abstract class Model implements \Serializable {

	protected $modelName;

	protected $primaryKey = 'id';

	protected $exist;

	protected $table;

	protected $origin;

	protected $properties;

	protected $relations;

	protected $query;

	protected $guarded = ['created_at', 'updated_at'];

	protected $event = [];

	protected $fill = [];

	protected $hidden;

	public function __construct(Array $attributes = []) {
		$this->boot();

		$this->relations = new Collection;
		$this->fill($attributes);
	}

	public function boot($exist = false) {
		$this->modelName = get_class($this);

		$this->query = new QueryManager($this->getTable());
		$this->query->bindModel($this);

		$this->setExist($exist);
	}

	public function fill($attributes) {
		$this->origin = new Collection($attributes);
		$this->properties = new Collection($attributes);

		$this->setExist(!empty($attributes));
	}

	public function getTable() {
		if (isset($this->table)) {
			return $this->table;
		}

		return $this->modelName;
	}

	public function getModelName($full = false) {
		if ($full) {
			return $this->modelName;
		}

		$modelName = explode("\\", $this->modelName);

		return array_pop($modelName);
	}

	public function setExist($exist) {
		$this->exist = $exist;
	}

	public function isExist() {
		return $this->exist;
	}

	public function save() {
		return $this->exist ? $this->query->update($this->properties) : $this->query->insert($this->properties);
	}

	public function update() {
		return $this->query->update($this->properties);
	}

	public function with($relations) {
		if (is_string($relations)) {
			$relations = func_get_args();
		}

		if (!isset($this->relation)) {
			return false;
		}

		$this->preProcessRelations($relations);
		$this->processRelations();

		return $this;
	}

	public function preProcessRelations($relations) {
		foreach ($relations as $relationName) {
			if (!$this->relations->has($relationName) && $relation = $this->getRelation($relationName)) {

				$model = $relationName === 'sub' ? static::class : $relationName;

				$type = $relation[0] === 'many' ? 'get' : 'one';
				$foreign_key = $relation[1];
				$local_key = isset($relation[2]) ? $relation[2] : 'id';

				$this->relations->set($relationName, compact('model', 'type', 'foreign_key', 'local_key'));
			}
		}
	}

	public function processRelations() {
		if (!$this->isExist() || $this->relations->length() === 0) {
			return;
		}

		if (func_num_args() === 1) {
			echo 1;
		}

		foreach ($this->getRelations() as $relationName => $relation) {
			if (is_object($relation) || !$relation) {
				continue;
			}

			$instance = new $relation['model'];

			if ($instance = $instance->where($relation['foreign_key'], $this->{$relation['local_key']})->{$relation['type']}()) {
				$this->relations->set($relationName, $instance);
			} else {
				$this->relations->set($relationName, false);
			}
		}
	}

	public function getRelations() {
		return $this->relations;
	}

	public function getRelation($relation) {
		if (!isset($this->relation) || !isset($this->relation[$relation])) {
			return false;
		}

		return $this->relation[$relation];
	}

	public function getOrigin() {
		return $this->origin->filter([$this->primaryKey]);
	}

	public function setOrigin($origin) {
		$this->origin->replace($origin);
	}

	public function getProperties($full = false) {
		if ($full) {
			return $this->properties;
		}

		return $this->properties->filter([$this->primaryKey]);
	}

	public function setProperties($properties) {
		if ($this->properties->length() > 0) {
			return;
		}

		$this->properties->replace($properties);
	}

	public function getFill() {
		return $this->fill;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function getGuarded() {
		return $this->guarded;
	}

	protected function getHidden() {
		return $this->hidden ? $this->hidden : [];
	}

	public function setTimestamp($type = 'both') {
		switch ($type) {
		case 'both':
			$this->created_at = date('Y-m-d H:i:s');
			$this->updated_at = date('Y-m-d H:i:s');
			break;
		case 'update':
			$this->updated_at = date('Y-m-d H:i:s');
			break;
		}
	}

	public function isSoftDelete() {
		return isset($this->softDelete) ? $this->softDelete : false;
	}

	public function setSoftDelete() {
		$this->deleted_at = date('Y-m-d H:i:s');
	}

	public function __get($key) {
		if ($relation = $this->getRelation($key)) {
			$this->with([$key]);
			return $this->relations->get($key);
		}

		return $this->properties->get($key);
	}

	public function __set($key, $value) {
		$this->properties->set($key, $value);
	}

	public function serialize() {
		return serialize($this->properties);
	}

	public function unserialize($data) {
		$data = unserialize($data);

		$this->properties = $data;
		$this->origin = $data;

		$this->relations = new Collection;

		$this->boot(true);
	}

	public function __call($method, $params) {
		$call = call_user_func_array([$this->query, $method], $params);

		return $call === $this->query ? $this : $call;
	}

	public static function __callStatic($method, $params) {
		$instance = new static;

		call_user_func_array([$instance->query, $method], $params);

		return $instance;
	}
}
