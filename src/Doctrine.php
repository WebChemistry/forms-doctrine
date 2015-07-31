<?php

namespace WebChemistry\Forms;

use Nette;

use Doctrine as Doc;

class Doctrine extends Nette\Object {

	/** @var Doc\ORM\EntityManager */
	private $em;

	/**
	 * @param Doc\ORM\EntityManager $em
	 */
	public function __construct(Doc\ORM\EntityManager $em) {
		$this->em = $em;
	}

	/**
	 * @param string $name
	 * @param mixed $items
	 * @return array
	 */
	private function getItems($name, $items) {
		return array_key_exists($name, $items) && is_array($items[$name]) ? $items[$name] : array();
	}

	/**
	 * @param object $entity
	 * @param array  $items
	 * @return array
	 */
	private function buildArray($entity, array $items = array()) {
		$meta = $this->em->getClassMetadata(get_class($entity));
		$return = array();

		foreach ($meta->columnNames as $name => $void) {
			if ($this->checkItem($name, $items)) {
				$return[$name] = $entity->$name;
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if ($info['isOwningSide'] === FALSE) {
				continue;
			}

			if (!isset($entity->$name) || !$this->checkItem($name, $items, TRUE)) {
				continue;
			}

			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
				$return[$name] = array();

				foreach ($entity->$name as $index => $row) {
					$return[$name][$index] = $this->buildArray($row, $this->getItems($name, $items));
				}

				continue;
			}

			if (!$entity->$name instanceof $info['targetEntity']) {
				if ($this->checkItem($name, $items, TRUE)) {
					$return[$name] = NULL; // Empty entity
				}

				continue;
			}

			$return[$name] = $this->buildArray($entity->$name, $this->getItems($name, $items));
		}

		return $return;
	}

	/**
	 * Transform entity to array
	 *
	 * @param object $entity
	 * @param array  $items
	 * @return array
	 * @throws Exception
	 */
	public function toArray($entity, array $items = array()) {
		if (!is_object($entity)) {
			throw new \Exception('Entity must be object.');
		}

		return $this->buildArray($entity, $this->parseItems($items));
	}

	/**
	 * @param object $entity
	 * @param array  $values
	 * @param array  $items
	 * @return object
	 */
	public function buildEntity($entity, array $values, array $items) {
		$meta = $this->em->getClassMetadata(get_class($entity));

		foreach ($meta->columnNames as $name => $void) {
			if (array_key_exists($name, $values) && $this->checkItem($name, $items)) {
				$entity->$name = $values[$name];
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if ($info['isOwningSide'] === FALSE) {
				continue;
			}

			if (!array_key_exists($name, $values) || !$this->checkItem($name, $items, TRUE)) {
				continue;
			}

			if ($info['type'] === Doc\ORM\Mapping\ClassMetadata::MANY_TO_MANY) {
				foreach ($values[$name] as $row) {
					if (!is_array($row)) {
						continue; // Exception?
					}

					call_user_func(array($entity, 'add' . $name), $this->buildEntity(new $info['targetEntity'], $row, $this->getItems($name, $items)));
				}

				continue;
			}

			if (!$entity->$name instanceof $info['targetEntity']) {
				$entity->$name = new $info['targetEntity'];
			}

			if (!is_array($values[$name])) {
				$entity->$name = NULL;
				continue; // Exception?
			}

			$entity->$name = $this->buildEntity($entity->$name, $values[$name], $this->getItems($name, $items));
		}

		return $entity;
	}

	/**
	 * Transform array to entity
	 *
	 * @param object|string $entity
	 * @param array  $values
	 * @param array  $items
	 * @return mixed
	 */
	public function toEntity($entity, array $values, array $items = array()) {
		if (!is_object($entity)) {
			$entity = new $entity;
		}

		return $this->buildEntity($entity, $values, $this->parseItems($items));
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private function parseItems(array $items) {
		if (!$items) {
			return array();
		}

		$result = array();
		$isAll = TRUE;


		foreach ($items as $name => $value) {
			if (is_int($name)) {
				if (!Nette\Utils\Strings::startsWith($value, '~')) {
					$isAll = FALSE;
				}

				$result[$value] = '*';
			} else {
				if (!Nette\Utils\Strings::startsWith($name, '~')) {
					$isAll = FALSE;
				}

				$result[$name] = is_array($value) ? $this->parseItems($value) : $value;
			}
		}

		if ($isAll) {
			$result['*'] = '*';
		}

		return $result;
	}

	/**
	 * @param string     $name
	 * @param array|bool $items
	 * @param bool       $isAssociation
	 * @return bool
	 */
	private function checkItem($name, $items, $isAssociation = FALSE) {
		if (!$items) {
			return TRUE;
		}

		// Exclude
		if (array_key_exists('~' . $name, $items)) {
			return FALSE;
		}

		// Allow all
		if (array_key_exists('*', $items)) {
			return TRUE;
		}

		if ($isAssociation) {
			return array_key_exists($name, $items);
		}

		return array_key_exists($name, $items);
	}
}
