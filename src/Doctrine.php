<?php

namespace WebChemistry\Forms;

use Doctrine as Doc;
use Nette\SmartObject;
use WebChemistry\Forms\Doctrine\Settings;

class Doctrine {

	use SmartObject;

	/** @var Doc\ORM\EntityManager */
	private $em;

	/** @var array */
	private $path = array();

	/** @var Settings */
	private $settings;

	/** @var array|object */
	private $original;

	/**
	 * @param Doc\ORM\EntityManager $em
	 */
	public function __construct(Doc\ORM\EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * Transform entity to array
	 *
	 * @param object $entity
	 * @param Settings|NULL $settings
	 * @return array
	 * @throws \Exception
	 */
	public function toArray($entity, Settings $settings = NULL) {
		if (!is_object($entity)) {
			throw new \Exception('Entity must be object.');
		}

		$this->original = $entity;
		$this->path = array();
		$this->settings = $settings ? : new Settings;

		return $this->buildArray($entity);
	}

	/**
	 * Transform array to entity
	 *
	 * @param object|string $entity
	 * @param array|\Traversable $values
	 * @param Settings $settings
	 * @return mixed
	 */
	public function toEntity($entity, $values, Settings $settings = NULL) {
		$reflection = new \ReflectionClass($entity);
		if (!is_object($entity)) {
			$method = $reflection->getConstructor();
			if (!$method || $method->getNumberOfRequiredParameters() === 0) {
				$entity = new $entity;
			} else {
				$entity = new \stdClass();
			}
		}
		if ($values instanceof \Traversable) {
			$values = $this->recursiveIteratorToArray($values);
		}

		$this->original = $values;
		$this->path = array();
		$this->settings = $settings ?: new Settings;

		return $this->buildEntity($entity, $reflection, $values);
	}

	/************************* Builders **************************/

	/**
	 * @param object $entity
	 * @return array
	 */
	protected function buildArray($entity) {
		$meta = $this->em->getClassMetadata(get_class($entity));
		$return = array();

		foreach ($meta->columnNames as $name => $void) {
			if ($this->checkItem($name)) {
				// Custom callback
				if ($callback = $this->settings->getCallback($this->getPathName($name))) {
					$return[$name] = $callback($this->propertyGet($entity, $name), $this->original);
				} else {
					$return[$name] = $this->propertyGet($entity, $name);
				}
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!$this->checkItem($name) || $info['isOwningSide'] === FALSE || !property_exists($entity, $name)) {
				continue;
			}
			$propertyGet = $this->propertyGet($entity, $name);

			// Custom callback
			if ($callback = $this->settings->getCallback($this->getPathName($name))) {
				// Can be use as reference
				$continue = FALSE;
				$return[$name] = $callback($propertyGet, $this->original, $continue);
				if (!$continue) {
					continue;
				}
			}
			// ManyToMany
			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
				$return[$name] = array();
				foreach ($propertyGet as $index => $row) {
					$this->path[] = $name;
					$return[$name][$index] = $this->buildArray($row);
					array_pop($this->path);
				}

				continue;
			}
			// Given value is not target entity
			if (!$propertyGet instanceof $info['targetEntity']) {
				if ($this->checkItem($name)) {
					$return[$name] = NULL; // Empty entity
				}

				continue;
			}

			if ($joinColumn = $this->settings->getJoinOneColumn($this->getPathName($name))) {
				if (!is_callable($joinColumn)) {
					$return[$name] = $this->propertyGet($propertyGet, $joinColumn);
				} else {
					$joinColumn($propertyGet, $return);
				}

				continue;
			}

			$this->path[] = $name;
			$return[$name] = $this->buildArray($propertyGet);
			array_pop($this->path);
		}

		return $return;
	}

	/**
	 * @param object $entity
	 * @param \ReflectionClass $reflection
	 * @param array $values
	 * @throws DoctrineException
	 * @return object
	 */
	protected function buildEntity($entity, $reflection, array $values) {
		$className = $reflection->getName();
		$meta = $this->em->getClassMetadata($className);

		// Normal items without associate
		foreach ($meta->columnNames as $name => $void) {
			if (array_key_exists($name, $values) && $this->checkItem($name)) {
				// Custom callback
				$callback = $this->settings->getCallback($this->getPathName($name));
				if ($callback) {
					$this->propertySet($entity, $name, $callback($values[$name], $this->original));
				} else {
					$this->propertySet($entity, $name, $values[$name]);
				}
			}
		}

		// associate
		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!$this->checkItem($name) || !array_key_exists($name, $values)) {
				continue;
			}
			if ($info['isOwningSide'] === FALSE && !in_array('persist', $info['cascade'])) {
				continue;
			}

			// Custom callback
			if ($callback = $this->settings->getCallback($this->getPathName($name))) {
				// Can be use as reference
				$continue = TRUE;
				$this->propertySet($entity, $name, $callback($values[$name], $this->original, $continue));
				if ($continue) {
					continue;
				}
			}
			// ManyToMany & cascade
			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY || $info['isOwningSide'] === FALSE) {
				if (!is_array($values[$name])) {
					continue;
				}
				$arr = array();
				foreach ($values[$name] as $row) {
					if (!$row instanceof $info['targetEntity']) {
						if (is_array($row)) {
							$reflection = new \ReflectionClass($info['targetEntity']);
							if ($reflection->getConstructor()->getNumberOfRequiredParameters() === 0) {
								$obj = new $info['targetEntity'];
							} else {
								$obj = new \stdClass();
							}

							$this->path[] = $name;
							$row = $this->buildEntity($obj, $reflection, $row);
							array_pop($this->path);

						} else if ($this->isInFind($name)) { // Find by id
							$row = $this->em->getRepository($info['targetEntity'])->find($row);
							if (!$row) {
								continue;
							}

						} else {
							continue; // Exception?
						}
					}

					$arr[] = $row;
				}

				$this->propertySet($entity, $name, $arr);
				continue;
			}
			// Array contains entity of target
			if ($values[$name] instanceof $info['targetEntity']) {
				$this->propertySet($entity, $name, $values[$name]);
				continue;
			}

			if (!is_array($values[$name])) {
				$val = NULL;
				// Find by id
				if ($this->isInFind($name)) {
					if ($values[$name] !== NULL) {
						$val = $this->em->getRepository($info['targetEntity'])->find($values[$name]);
					}
				}

				$this->propertySet($entity, $name, $val);
				continue;
			}
			// Target is null or other object
			$obj = $this->propertyGet($entity, $name);
			if (!$obj instanceof $info['targetEntity']) {
				$ref = new \ReflectionClass($info['targetEntity']);
				$method = $ref->getConstructor();
				if (!$method || $method->getNumberOfRequiredParameters() === 0) {
					$obj = new $info['targetEntity'];
				} else {
					$obj = new \stdClass();
				}
			} else {
				$ref = new \ReflectionClass($obj);
			}

			$this->path[] = $name;
			$obj = $this->buildEntity($obj, $ref, $values[$name]);
			$this->propertySet($entity, $name, $obj);
			array_pop($this->path);
		}

		// Convert stdClass to entity class
		if (!$entity instanceof $className) {
			$args = array();
			foreach ($reflection->getConstructor()->getParameters() as $param) {
				$name = $param->getName();
				if (!property_exists($entity, $name)) {
					if (!$param->isOptional()) {
						throw new DoctrineException("Required parameter '$name' not exists for '$className'.");
					}
					$args[] = $param->getDefaultValue();
					continue;
				}

				$args[] = $entity->$name;
				unset($entity->$name);
			}

			$std = $entity;
			$entity = $reflection->newInstanceArgs($args);

			foreach ($std as $key => $value) {
				$this->propertySet($entity, $key, $value);
			}
		}

		return $entity;
	}

	/************************* Helpers **************************/

	/**
	 * @param \Traversable $traversable
	 * @return array
	 */
	private function recursiveIteratorToArray(\Traversable $traversable) {
		$array = array();
		foreach ($traversable as $item => $value) {
			if ($value instanceof \Traversable) {
				$value = $this->recursiveIteratorToArray($value);
			}
			$array[$item] = $value;
		}

		return $array;
	}

	private function propertyGet($class, $name) {
		$getter = 'get' . ucfirst($name);
		if (!$class instanceof \stdClass && method_exists($class, $getter)) {
			return call_user_func(array($class, $getter));
		}

		return isset($class->$name) ? $class->$name : NULL;
	}

	private function propertySet($class, $name, $value) {
		if ($class instanceof \stdClass) {
			$class->$name = $value;
			
			return TRUE;
		}

		$setter = 'set' . ucfirst($name);
		if (method_exists($class, $setter)) {
			call_user_func(array($class, $setter), $value);
			
			return TRUE;
		}
		if (is_array($value)) { // adder
			$adder = 'add' . ucfirst($name);
			for ($i = 0; $i < 3; $i++) { // Plural version with s, es
				if (method_exists($class, $adder)) {
					foreach ($value as $item) {
						call_user_func(array($class, $adder), $item);
					}

					return TRUE;
				}
				$adder = substr($adder, 0, -1);
			}
		}
		if (property_exists($class, $name)) {
			$class->$name = $value;
		} else {
			return FALSE;
		}
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function getPathName($name) {
		return implode('.', $this->path) . ($this->path ? '.' : '') . $name;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function checkItem($name) {
		return $this->settings->getAllowedItems($this->getPathName($name));
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function isInFind($name) {
		return $this->settings->isInFind($this->getPathName($name));
	}

}
