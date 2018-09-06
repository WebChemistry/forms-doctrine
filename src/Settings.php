<?php

namespace WebChemistry\Forms\Doctrine;

use Nette\SmartObject;
use Nette\Utils\Callback;

class Settings {

	use SmartObject;

	/** @var array */
	protected $joinColumn = array();

	/** @var array */
	protected $allowedItems = array();

	/** @var array item => callback */
	protected $callbacks = array();

	/** @var array */
	protected $find = [];

	/**
	 * @param string $name
	 * @return string|NULL
	 */
	public function getJoinOneColumn($name) {
		if (array_key_exists($name, $this->joinColumn)) {
			return $this->joinColumn[$name];
		}
	}

	/**
	 * @param array $joinColumn
	 * @return self
	 */
	public function setJoinOneColumn(array $joinColumn) {
		$this->joinColumn = $joinColumn;

		return $this;
	}

	/**
	 * @param string $name
	 * @param array $prev
	 * @return array
	 */
	public function getAllowedItems($name, array $prev = NULL) {
		if (!$this->allowedItems) {
			return TRUE;
		}

		$allowedItems = $prev !== NULL ? $prev : $this->allowedItems;

		if (array_key_exists($name, $allowedItems)) {
			return TRUE;
		}

		// ['*']
		if (($key = array_search('*', $allowedItems)) !== FALSE && is_numeric($key)) {
			return TRUE;
		}

		// path.name.*
		if (strpos($name, '.')) {
			if (array_key_exists($first = substr($name, 0, strpos($name, '.')), $allowedItems)) {
				$name = substr($name, strpos($name, '.') + 1);

				return $this->getAllowedItems($name, (array) $allowedItems[$first]);
			} else {
				return FALSE;
			}
		}

		if (array_search($name, $allowedItems) !== FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param array $allowedItems
	 * @return self
	 */
	public function setAllowedItems(array $allowedItems) {
		$this->allowedItems = $allowedItems;

		return $this;
	}

	/**
	 * @param array $callbacks
	 * @return self
	 */
	public function setCallbacks(array $callbacks) {
		$this->callbacks = $callbacks;

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getCallback($item) {
		return isset($this->callbacks[$item]) ? Callback::check($this->callbacks[$item]) : NULL;
	}

	/**
	 * @param array $items
	 * @return self
	 */
	public function setFind(array $items) {
		$this->find = $items;

		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isInFind($name) {
		return array_search($name, $this->find) !== FALSE;
	}

}
