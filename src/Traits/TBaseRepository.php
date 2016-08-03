<?php

namespace WebChemistry\Forms\Doctrine;

use WebChemistry\Forms\Doctrine;

trait TBaseRepository {

	/** @var Doctrine */
	private $converter;

	/**
	 * @return Doctrine
	 */
	protected function getConverter() {
		if (!$this->converter) {
			$this->converter = new Doctrine($this->_em);
		}

		return $this->converter;
	}

	/**
	 * @param array $values
	 * @param Settings $settings
	 * @param string $defaultEntity
	 * @return object
	 */
	protected function convertToEntity(array $values, $defaultEntity = NULL, Settings $settings = NULL) {
		return $this->getConverter()->toEntity($defaultEntity ?: $this->getEntityName(), $values, $settings);
	}

	/**
	 * @param object $entity
	 * @param Settings $settings
	 * @return array
	 */
	protected function convertToArray($entity, Settings $settings = NULL) {
		return $this->getConverter()->toArray($entity, $settings);
	}

}
