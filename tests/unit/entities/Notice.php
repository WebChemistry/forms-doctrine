<?php

namespace Tests;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Notice {

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="integer", length=11)
	 * @ORM\GeneratedValue()
	 */
	protected $id;

	/**
	 * @ORM\OneToOne(targetEntity="Tests\User", mappedBy="notice")
	 */
	protected $user;

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
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param mixed $user
	 * @return self
	 */
	public function setUser($user) {
		$this->user = $user;

		return $this;
	}

}
