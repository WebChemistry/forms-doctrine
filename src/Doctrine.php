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

	private function buildArray($entity, array $items = array(), $primary = FALSE) {
		$meta = $this->em->getClassMetadata(get_class($entity));
		$return = array();

		foreach ($meta->columnNames as $name => $void) {
			if ($this->checkItem($name, $items)) {
				$return[$name] = $entity->$name;
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!isset($entity->$name) || !$entity->$name instanceof $info['targetEntity'] || !$this->checkItem($name, $items, TRUE)) {
				continue;
			}

			if ($primary === FALSE) {
				$return[$name] = $this->buildArray($entity->$name, array_key_exists($name, $items) && is_array($items[$name]) ? $items[$name] : array(), $primary);
			} else {
				if (method_exists($info['targetEntity'], '__toString')) {
					$return[$name] = (string) $entity->$name;

					continue;
				}

				$targetMeta = $this->em->getClassMetadata($info['targetEntity']);
				$ids = $targetMeta->getIdentifier();

				if (!$ids) {
					throw new Exception("Entity $info[targetEntity] does not have identifier.");
				}

				$id = reset($ids);

				$return[$name] = $entity->$id;
			}
		}

		return $return;
	}

	/**
	 * Transform entity to array
	 *
	 * @param object $entity
	 * @param array  $items
	 * @param bool   $primary
	 * @return array
	 * @throws Exception
	 */
	public function toArray($entity, array $items = array(), $primary = FALSE) {
		if (!is_object($entity)) {
			throw new \Exception('Entity must be object.');
		}

		return $this->buildArray($entity, $this->parseItems($items), $primary);
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
			if (!array_key_exists($name, $values) || !$this->checkItem($name, $items, TRUE)) {
				continue;
			}

			if (!$entity->$name instanceof $info['targetEntity']) {
				$entity->$name = new $info['targetEntity'];
			}

			if (!is_array($values[$name]) && $values[$name] !== NULL) {
				$entity->$name = NULL;
				continue; // Exception?
			}

			$entity->$name = $this->buildEntity($entity->$name, $values[$name], array_key_exists($name, $items) && is_array($items[$name]) ? $items[$name] : array());
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
		$result = array();

		foreach ($items as $name => $value) {
			if (is_string($value)) {
				$result[$value] = TRUE;
			} else if (is_array($value)) {
				$result[$name] = $this->parseItems($value);
			} else if (is_bool($value)) {
				$result[$name] = $value;
			}
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

		if ($isAssociation) {
			return array_key_exists($name, $items) && $items[$name] !== FALSE;
		}

		return array_key_exists($name, $items);
	}
}
