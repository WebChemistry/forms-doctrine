<?php

class EntityTest extends \PHPUnit_Framework_TestCase {

	/** @var \WebChemistry\Forms\Doctrine */
	protected $helper;

	protected function setUp() {
		$config = new \Kdyby\Doctrine\Configuration();
		$mapping = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new \Doctrine\Common\Annotations\AnnotationReader(), [__DIR__ . '/entitites']);
		$config->setMetadataDriverImpl($mapping);
		$config->setProxyDir(__DIR__ . '/proxy');
		$config->setProxyNamespace('Tests\_ProxyTests');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(__DIR__ . '/../../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');
		$event = new \Doctrine\Common\EventManager();
		$conn = new \Kdyby\Doctrine\Connection(array(
			'dbname' => 'test',
			'user' => 'travis',
			'password' => '',
			'host' => 'localhost',
			'driver' => 'pdo_mysql',
		), new \Doctrine\DBAL\Driver\PDOMySql\Driver(), $config, $event);
		$em = \Kdyby\Doctrine\EntityManager::create($conn, $config, $event);
		$this->helper = new \WebChemistry\Forms\Doctrine($em);
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
		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(array(
			'id', 'name', 'role' => array('id'), 'history' => array('*'), 'cart' => array('name')
		));

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
		), $this->helper->toArray($this->fillEntity(), $settings));
	}

	public function testManyToMany() {
		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(['name']);

		$this->assertSame(array('name' => 'John'), $this->helper->toArray($this->fillEntity(), $settings));

		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(['cart' => '*']);

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
		), $this->helper->toArray($this->fillEntity(), $settings));
	}

	public function testCallback() {
		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setAllowedItems(['name', 'role']);
		$settings->setCallbacks([
			'name' => function ($value, $entity) {
				return 'myValue';
			},
			'role' => function ($value, $entity, &$continue) {
				return 'myValue';
			}
		]);
		$this->assertSame(['name' => 'myValue', 'role' => 'myValue'], $this->helper->toArray($this->fillEntity(), $settings));
	}

	public function testJoinColumn() {
		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setJoinOneColumn([
			'role' => 'id'
		]);
		$settings->setAllowedItems(['role' => ['*']]);

		$this->assertSame(['role' => 5], $this->helper->toArray($this->fillEntity(), $settings));
	}

}
