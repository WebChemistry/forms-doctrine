<?php

namespace Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Item {

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer", length=11)
	 * @ORM\GeneratedValue()
	 */
	protected $id;

	/**
	 * @ORM\Column(type="name")
	 */
	protected $name;

	/**
	 * @ORM\ManyToMany(targetEntity="Tests\User", mappedBy="items")
	 */
	private $users;

	public function __construct() {
		$this->users = new ArrayCollection();
	}

	public function addUser(User $user) {
		$this->users->add($user);
	}

	public function getUsers() {
		return $this->users;
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
