<?php

namespace Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Role {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", length=11)
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=15)
	 */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Tests\User", mappedBy="role", cascade={"persist"})
	 */
	protected $users;

	/**
	 * @ORM\Column(type="string", length=15)
	 */
	public $public;

	public function __construct() {
		$this->users = new ArrayCollection();
	}

	public function addUser(User $user) {
		$this->users->add($user);
		$user->setRole($this);
	}
	
	public function getUsers() {
		return $this->users;
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

}
