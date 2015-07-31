<?php

class EntityTest extends \PHPUnit_Framework_TestCase {

	/** @var \WebChemistry\Forms\Doctrine */
	protected $helper;

	protected function setUp() {
		$this->helper = E::getByType('WebChemistry\Forms\Doctrine');
	}

	protected function tearDown() {
	}

	private function fillArray() {
		return array(
			'id' => 1,
			'name' => 'John',
			'password' => 'myPassword',
			'registration' => NULL,
			'count' => 5,
			'role' => array(
				'id' => 5,
				'name' => 'Owner'
			),
			'history' => array(
				'id' => NULL,
				'name' => 'History'
			),
			'cart' => array(
				0 => array(
					'id' => NULL,
					'name' => 'Cart 1'
				),
				1 => array(
					'id' => 2,
					'name' => 'Cart 2'
				)
			),
			'voidClass' => NULL
		);
	}

	private function fillEntity() {
		$entity = new \Entity\User();
		$entity->id = 1;
		$entity->name = 'John';
		$entity->password = 'myPassword';
		$entity->count = 5;

		$role = new \Entity\Role();
		$role->id = 5;
		$role->name = 'Owner';
		$role->addUser($entity);
		$entity->role = $role;

		$history = new \Entity\History;
		$history->name = 'History';
		$history->user = $entity;
		$entity->history = $history;

		$cart = new \Entity\Cart();
		$cart->name = 'Cart 1';
		$cart->addUser($entity);

		$cart = new \Entity\Cart();
		$cart->id = 2;
		$cart->name = 'Cart 2';
		$cart->addUser($entity);

		return $entity;
	}

	public function testBase() {
		$entity = $this->fillEntity();

		$this->assertSame(array(
			'id' => 1,
			'name' => 'John',
			'password' => 'myPassword',
			'registration' => NULL,
			'count' => 5,
			'role' => array(
				'id' => 5,
				'name' => 'Owner'
			),
			'history' => array(
				'id' => NULL,
				'name' => 'History'
			),
			'cart' => array(
				0 => array(
					'id' => NULL,
					'name' => 'Cart 1'
				),
				1 => array(
					'id' => 2,
					'name' => 'Cart 2'
				)
			),
			'voidClass' => NULL
		), $this->helper->toArray($entity));
	}

	public function testItems() {
		$items = array(
			'id', 'name', 'role' => array('id'), 'history' => '*', 'cart' => array('name')
		);

		$this->assertSame(array(
			'id' => 1,
			'name' => 'John',
			'role' => array(
				'id' => 5
			),
			'history' => array(
				'id' => NULL,
				'name' => 'History'
			),
			'cart' => array(
				0 => array(
					'name' => 'Cart 1'
				),
				1 => array(
					'name' => 'Cart 2'
				)
			)
		), $this->helper->toArray($this->fillEntity(), $items));
	}

	public function testManyToMany() {
		$items = array('name');

		$this->assertSame(array('name' => 'John'), $this->helper->toArray($this->fillEntity(), $items));

		$items = array('cart' => '*');

		$this->assertSame(array(
			'cart' => array(
				0 => array(
					'id' => NULL,
					'name' => 'Cart 1'
				),
				1 => array(
					'id' => 2,
					'name' => 'Cart 2'
				)
			)
		), $this->helper->toArray($this->fillEntity(), $items));
	}

	public function testExcludeItems() {
		$items = array(
			'*', '~history', '~cart', '~name', '~count', 'role' => array('~name')
		);

		$this->assertSame(array(
			'id' => 1,
			'password' => 'myPassword',
			'registration' => NULL,
			'role' => array(
				'id' => 5
			),
			'voidClass' => NULL
		), $this->helper->toArray($this->fillEntity(), $items));
	}

	public function testExcludeItemsManyToMany() {
		$items = array(
			'cart' => array('~id')
		);

		$this->assertSame(array(
			'cart' => array(
				0 => array(
					'name' => 'Cart 1'
				),
				1 => array(
					'name' => 'Cart 2'
				)
			)
		), $this->helper->toArray($this->fillEntity(), $items));
	}
}
