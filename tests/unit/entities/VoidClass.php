<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 */
class VoidClass extends BaseEntity {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer", length=9)
	 * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\OneToOne(targetEntity="Entity\User", mappedBy="user")
	 */
	protected $user;

	public function setUser(User $user) {
		$this->user = $user;
		$user->history = $this;
	}
}