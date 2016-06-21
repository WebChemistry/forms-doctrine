<?php

namespace Tests;

use Doctrine\ORM\EntityRepository;

class DefaultRepository extends EntityRepository {

	/** @var array */
	public static $find = [];

	public function find($id, $lockMode = NULL, $lockVersion = NULL) {
		self::$find[$this->getEntityName()][] = $id;
		$entity = $this->getEntityName();

		return new $entity;
	}

}
