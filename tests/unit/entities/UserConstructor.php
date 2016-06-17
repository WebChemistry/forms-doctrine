<?php

namespace Tests;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class UserConstructor {

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer", length=9)
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $optionalName;

	/**
	 * @ORM\ManyToOne(targetEntity="Tests\Role")
	 */
	protected $role;

	/**
	 * @ORM\ManyToOne(targetEntity="Tests\Role")
	 */
	protected $optionalRole;

	public function __construct($id, $role, $name = 'name', $optionalName = NULL) {
		$this->id = $id;
		$this->role = $role;
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 * @return self
	 */
	public function setId($id) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 * @return self
	 */
	public function setName($name) {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getOptionalName() {
		return $this->optionalName;
	}

	/**
	 * @param mixed $optionalName
	 * @return self
	 */
	public function setOptionalName($optionalName) {
		$this->optionalName = $optionalName;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRole() {
		return $this->role;
	}

	/**
	 * @param mixed $role
	 * @return self
	 */
	public function setRole($role) {
		$this->role = $role;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getOptionalRole() {
		return $this->optionalRole;
	}

	/**
	 * @param mixed $optionalRole
	 * @return self
	 */
	public function setOptionalRole($optionalRole) {
		$this->optionalRole = $optionalRole;

		return $this;
	}

}
