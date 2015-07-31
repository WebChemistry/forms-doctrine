<?php

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\BaseEntity;

/**
 * @ORM\Entity
 */
class User extends BaseEntity {

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
	 * @ORM\Column(type="string", length=50)
	 */
	protected $password;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $registration;

	/**
	 * @ORM\Column(type="integer", length=5)
	 */
	protected $count;

	/**
	 * @ORM\ManyToOne(targetEntity="Entity\Role", inversedBy="users", cascade={"persist"})
	 */
	protected $role;

	/**
	 * @ORM\OneToOne(targetEntity="Entity\History", inversedBy="user", cascade={"persist"})
	 */
	protected $history;

	/**
	 * @ORM\ManyToMany(targetEntity="Entity\Cart", inversedBy="users", cascade={"persist"})
	 */
	protected $cart;

	/**
	 * @ORM\OneToOne(targetEntity="Entity\VoidClass", inversedBy="user")
	 */
	protected $voidClass;

	public function __construct() {
		$this->cart = new ArrayCollection();
	}

	public function addCart(Cart $cart) {
		$this->cart->add($cart);
	}

	public function clearCart() {
		$this->cart->clear();
	}
}