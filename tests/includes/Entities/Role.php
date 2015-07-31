<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 */
class Role extends BaseEntity {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", length=9)
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=15)
	 */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Entity\User", mappedBy="id")
	 */
	protected $users;

	public function __construct() {
		$this->users = new ArrayCollection();
	}

	public function addUser(User $user) {
		$this->users->add($user);
		$user->role = $this;
	}
}