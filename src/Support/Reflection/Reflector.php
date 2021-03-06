<?php
namespace Chestnut\Support\Reflection;

use Chestnut\Contract\Support\Container as ContainerContract;

/**
 * @author Liyang Zhang <zhangliyang@zhangliyang.name>
 */
class Reflector {
	protected $object;
	protected $method;
	protected $type;
	protected $dependencies = [];
	protected $injected = false;
	protected $separator = '::';

	public function __construct($object, $method = null) {
		$this->init($object, $method);

		$this->analysis();
	}

	public function init($object, $method) {
		if (is_string($object) && strpos($object, $this->separator)) {
			list($object, $method) = explode($this->separator, $object);

			return $this->init($object, $method);
		}

		$this->object = $object;
		$this->method = $method;
	}

	public function isExists() {
		return $this->type ? true : false;
	}

	public function getReflectName() {
		if (is_string($this->object)) {
			return $this->object;
		}

		if (is_object($this->object)) {
			return get_class($this->object);
		}

		return 'Closure';
	}

	public function analysis() {
		try {
			$this->type = ReflectionAnalysis::analysis($this->object, $this->method);
			$this->dependencies = ReflectionAnalysis::getDependencies($this->object, $this->method);
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function inject($params = [], ContainerContract $c = null) {
		if ($this->injected()) {
			return true;
		}

		$dependencies = $this->getDependencies();
		$inject = [];
		$missing = [];

		foreach ($dependencies as $dependency) {
			list($name, $dependency) = $this->getDependency($dependency, $params, $c);

			$inject[$name] = $dependency;
		}

		if (!empty($missing)) {
			throw new \InvalidArgumentException('Missing ' . count($missing) . ' parameter [ ' . join($missing, ', ') . ' ] in ' . $this->getReflectName());
		}

		$this->injected = true;

		$this->dependencies = $inject;
	}

	public function getDependencies() {
		return $this->dependencies;
	}

	public function getDependency($dependency, $parameters, $container) {
		if (is_array($parameters) && array_key_exists($name = $dependency->name, $parameters)) {
			return [$name, $parameters[$name]];
		}

		if (is_null($container)) {
			return $dependency->isDefaultValueAvailable() ? [$dependency->name, $dependency->getDefaultValue()] : [false, $dependency->name];
		}

		$name = $dependency->getClass() ? $dependency->getClass()->name : $dependency->name;

		return $container->registered($name) ? [$name, $container->make($name)] : [false, $name];
	}

	public function injected() {
		return $this->injected || empty($this->dependencies);
	}

	public function resolve() {

		if (!$this->injected()) {
			throw new \RuntimeException('This builder has not inject dependencies');
		}

		$reflector = ReflectionAnalysis::getReflector($this->object, $this->method);

		if (is_callable($reflector)) {
			return call_user_func_array($reflector, $this->dependencies);
		}

		return $reflector->newInstanceArgs($this->dependencies);
	}
}
