<?php

namespace Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User {

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer", length=11)
	 * @ORM\GeneratedValue()
	 */
	private $id;

	/**
	 * @ORM\ManyToMany(targetEntity="Tests\Item", inversedBy="users")
	 */
	private $items;

	/**
	 * @ORM\ManyToOne(targetEntity="Tests\Role", inversedBy="users")
	 */
	private $role;

	/**
	 * @ORM\OneToOne(targetEntity="Tests\Notice", inversedBy="user")
	 */
	private $notice;

	public function __construct() {
		$this->items = new ArrayCollection();
	}

	public function addItem(Item $item) {
		$this->items->add($item);
		$item->addUser($this);
	}

	public function getItems() {
		return $this->items;
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
	 * @return Role
	 */
	public function getRole() {
		return $this->role;
	}

	public function setRole($role) {
		$this->role = $role;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getNotice() {
		return $this->notice;
	}

	/**
	 * @param mixed $notice
	 * @return self
	 */
	public function setNotice($notice) {
		$this->notice = $notice;
		$notice->setUser($this);

		return $this;
	}

}
