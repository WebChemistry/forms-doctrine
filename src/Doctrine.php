<?php

namespace WebChemistry\Forms;

use Nette;

use Doctrine as Doc;
use Exception;

class Doctrine extends Nette\Object {

	/** @var Doc\ORM\EntityManager */
	private $em;

	/** @var array */
	private $items = array();

	/** @var bool */
	private $isItemsOriginal = TRUE;

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
	 * @param bool   $primary
	 * @return array
	 * @throws Exception
	 */
	public function toArray($entity, $primary = FALSE) {
		if (!is_object($entity)) {
			throw new Exception('Entity must be object.');
		}

		$meta = $this->em->getClassMetadata(get_class($entity));
		$return = array();

		foreach ($meta->columnNames as $name => $void) {
			$return[$name] = $entity->$name;
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!isset($entity->$name) || !$entity->$name instanceof $info['targetEntity']) {
				continue;
			}

			if ($primary === FALSE) {
				$return[$name] = $this->toArray($entity->$name, $primary);
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
	 * Transform array to entity
	 *
	 * @param object $entity
	 * @param array  $values
	 * @param array  $items
	 * @return mixed
	 */
	public function toEntity($entity, $values, array $items = array()) {
		if (!is_object($entity)) {
			$entityName = $entity;
			$entity = new $entity;
		} else {
			$entityName = get_class($entity);
		}

		$items = $this->setItems($items);

		$meta = $this->em->getClassMetadata($entityName);

		foreach ($meta->columnNames as $name => $void) {
			if (array_key_exists($name, $values) && $this->checkItem($name)) {
				$entity->$name = $values[$name];
			}
		}

		foreach ($meta->getAssociationMappings() as $name => $info) {
			if (!array_key_exists($name, $values) || !$entity->$name instanceof $info['targetEntity'] || !$this->checkItem($name, TRUE)) {
				continue;
			}

			$this->setItems($items);

			$entity->$name = $this->toEntity($entity->$name, $values[$name], array_key_exists($name, $items) ? $items[$name] : array());
		}

		$this->isItemsOriginal = TRUE;

		return $entity;
	}

	private function setItems(array $items) {
		if (!$this->isItemsOriginal) {
			$this->items = $items;

			return $this->items;
		}

		foreach ($items as $name) {
			if (strpos($name, '.') !== FALSE) {
				$current = &$this->items;

				foreach (explode('.', $name) as $row) {
					$current[$row] = array();
					$current = &$current[$row];
				}
			} else {
				$this->items[$name] = array();
			}
		}

		$this->isItemsOriginal = FALSE;

		return $this->items;
	}

	private function checkItem($name, $isAssociation = FALSE) {
		if (!$this->items) {
			return TRUE;
		}

		if ($isAssociation) {
			return array_key_exists($name, $this->items) && $this->items[$name];
		}

		return array_key_exists($name, $this->items);
	}
}
