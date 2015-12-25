<?php

namespace WebChemistry\Forms;

use Nette;

use Doctrine as Doc;
use WebChemistry\Forms\Doctrine\Settings;

class Doctrine extends Nette\Object {

	/** @var Doc\ORM\EntityManager */
	private $em;

	/** @var array */
	private $path = array();

	/** @var Settings */
	private $settings;

	/** @var array|entity */
	private $original;

	/**
	 * @param Doc\ORM\EntityManager $em
	 */
	public function __construct(Doc\ORM\EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * @param object $entity
	 * @return array
	 */
	private function buildArray($entity) {
		$meta = $this->em->getClassMetadata(get_class($entity));
		$return = array();

		foreach ($meta->columnNames as $name => $void) {
			if ($this->checkItem($name)) {
				// Custom callback
				if ($callback = $this->settings->getCallback($this->getPathName($name))) {
					$return[$name] = $callback($entity->$name, $this->original);
				} else {
					$return[$name] = $entity->$name;
				}
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!$this->checkItem($name)) {
				continue;
			}

			if ($info['isOwningSide'] === FALSE) {
				continue;
			}

			if (!isset($entity->$name)) {
				continue;
			}

			// Custom callback
			if ($callback = $this->settings->getCallback($this->getPathName($name))) {
				// Can be use as reference
				$continue = FALSE;

				$return[$name] = $callback($entity->$name, $this->original, $continue);

				if (!$continue) {
					continue;
				}
			}

			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
				$return[$name] = array();

				foreach ($entity->$name as $index => $row) {
					$this->path[] = $name;
					$return[$name][$index] = $this->buildArray($row);
					array_pop($this->path);
				}

				continue;
			}

			if (!$entity->$name instanceof $info['targetEntity']) {
				if ($this->checkItem($name)) {
					$return[$name] = NULL; // Empty entity
				}

				continue;
			}

			if ($joinColumn = $this->settings->getJoinOneColumn($this->getPathName($name))) {
				if (!is_callable($joinColumn)) {
					$return[$name] = $entity->$name->$joinColumn;
				} else {
					$joinColumn($entity->$name, $return);
				}

				continue;
			}

			$this->path[] = $name;
			$return[$name] = $this->buildArray($entity->$name);
			array_pop($this->path);
		}

		return $return;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function getPathName($name) {
		return implode('.', $this->path) . ($this->path ? '.' : '') . $name;
	}

	/**
	 * Transform entity to array
	 *
	 * @param object $entity
	 * @param array  $items
	 * @param Settings $settings
	 * @return array
	 * @throws Exception
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
	 * @param object $entity
	 * @param array  $values
	 * @param array  $items
	 * @return object
	 */
	public function buildEntity($entity, array $values) {
		$meta = $this->em->getClassMetadata(get_class($entity));

		foreach ($meta->columnNames as $name => $void) {
			if (array_key_exists($name, $values) && $this->checkItem($name)) {
				// Custom callback
				if ($callback = $this->settings->getCallback($this->getPathName($name))) {
					$entity->$name = $callback($values[$name], $this->original);
				} else {
					$entity->$name = $values[$name];
				}
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!$this->checkItem($name)) {
				continue;
			}

			if ($info['isOwningSide'] === FALSE) {
				continue;
			}

			if (!array_key_exists($name, $values)) {
				continue;
			}

			// Custom callback
			if ($callback = $this->settings->getCallback($this->getPathName($name))) {
				// Can be use as reference
				$continue = FALSE;

				$return[$name] = $callback($values[$name], $this->original, $continue);

				if (!$continue) {
					continue;
				}
			}

			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
				foreach ($values[$name] as $row) {
					if (!$row instanceof $info['targetEntity']) {
						if (is_array($row)) {
							$this->path[] = $name;
							$row = $this->buildEntity(new $info['targetEntity'], $row);
							array_pop($this->path);
						} else {
							continue; // Exception?
						}
					}

					call_user_func(array($entity, 'add' . $name), $row);
				}

				continue;
			}

			if (!$entity->$name instanceof $info['targetEntity']) {
				$entity->$name = new $info['targetEntity'];
			}

			if ($values[$name] instanceof $info['targetEntity']) {
				$entity->$name = $values[$name];
				continue;
			}

			if (!is_array($values[$name])) {
				$entity->$name = NULL;
				continue; // Exception?
			}

			$this->path[] = $name;
			$entity->$name = $this->buildEntity($entity->$name, $values[$name]);
			array_pop($this->path);
		}

		return $entity;
	}

	/**
	 * Transform array to entity
	 *
	 * @param object|string $entity
	 * @param array         $values
	 * @param Settings      $settings
	 * @return mixed
	 */
	public function toEntity($entity, array $values, Settings $settings = NULL) {
		if (!is_object($entity)) {
			$entity = new $entity;
		}

		$this->original = $values;
		$this->path = array();
		$this->settings = $settings ? : new Settings;

		return $this->buildEntity($entity, $values);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function checkItem($name) {
		return $this->settings->getAllowedItems($this->getPathName($name));
	}
}
