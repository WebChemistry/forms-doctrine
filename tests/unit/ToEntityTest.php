<?php

class ToEntityTest extends \PHPUnit_Framework_TestCase {

	/** @var \WebChemistry\Forms\Doctrine */
	protected $helper;

	protected function setUp() {
		$config = new \Kdyby\Doctrine\Configuration();
		$mapping = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(new \Doctrine\Common\Annotations\AnnotationReader(), [__DIR__ . '/entitites']);
		$config->setDefaultRepositoryClassName('Tests\DefaultRepository');
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

	public function testBase() {
		$arr = [
			'id' => 1,
			'name' => 'foo',
			'public' => 'bar'
		];

		/** @var \Tests\Role $entity */
		$entity = $this->helper->toEntity('Tests\Role', $arr);

		$this->assertInstanceOf('Tests\Role', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertSame('foo', $entity->getName());
		$this->assertSame('bar', $entity->public);
		$this->assertSame(0, $entity->getUsers()->count());
	}

	public function testNotExistsItems() {
		$arr = [
			'foo' => 'foo',
			'bar' => ['foo'],
			'items' => [ 'foo' ],
			'role' => [
				'foo' => 'foo'
			]
		];

		$entity = $this->helper->toEntity('Tests\User', $arr);

		$this->assertSame(0, $entity->getItems()->count());
		$this->assertInstanceOf('Tests\Role', $entity->getRole());
	}

	public function testSetEntity() {
		$arr = [
			'id' => 1,
			'name' => 'foo'
		];

		$entity = new \Tests\Role();
		$entity->public = 'bar';
		$entity->setName('bar');
		$entity = $this->helper->toEntity($entity, $arr);

		$this->assertInstanceOf('Tests\Role', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertSame('foo', $entity->getName());
		$this->assertSame('bar', $entity->public);
		$this->assertSame(0, $entity->getUsers()->count());
	}
	
	public function testSimpleAssociation() {
		$arr = [
			'id' => 1,
			'role' => [
				'name' => 'foo'
			]
		];
		
		/** @var \Tests\User $entity */
		$entity = $this->helper->toEntity('Tests\User', $arr);
		
		$this->assertInstanceOf('Tests\User', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertInstanceOf('Tests\Role', $entity->getRole());
		$this->assertSame('foo', $entity->getRole()->getName());
		$this->assertNull($entity->getRole()->getId());
		$this->assertNull($entity->getRole()->public);
	}

	public function testSimpleAssociationSetEntity() {
		$arr = [
			'id' => 1,
			'role' => [
				'name' => 'foo'
			]
		];

		$entity = new \Tests\User();
		$role = new \Tests\Role();
		$role->setName('bar');
		$role->public = 'foo';
		$entity->setRole($role);

		$entity = $this->helper->toEntity($entity, $arr);

		$this->assertInstanceOf('Tests\User', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertInstanceOf('Tests\Role', $entity->getRole());
		$this->assertSame('foo', $entity->getRole()->getName());
		$this->assertNull($entity->getRole()->getId());
		$this->assertSame('foo' , $entity->getRole()->public);
	}

	public function testOneToOne() {
		$arr = [
			'id' => 1,
			'notice' => [
				'id' => 1
			]
		];

		$entity = $this->helper->toEntity('Tests\User', $arr);

		$this->assertInstanceOf('Tests\Notice', $entity->getNotice());
		$this->assertSame(1, $entity->getNotice()->getId());
	}

	public function testManyToMany() {
		$arr = [
			'id' => 1,
			'items' => [
				['id' => 1],
				['id' => 2]
			],
		];

		/** @var \Tests\User $entity */
		$entity = $this->helper->toEntity('Tests\User', $arr);

		$this->assertInstanceOf('Tests\User', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertSame(2, $entity->getItems()->count());
		$this->assertSame(1, $entity->getItems()->get(0)->getId());
		$this->assertSame(2, $entity->getItems()->get(1)->getId());
	}
	
	public function testConstructor() {
		$array = array(
			'id' => 1,
			'role' => array(
				'id' => 1,
				'name' => 'roleName'
			),
			'optionalName' => 'name'
		);
		$entity = $this->helper->toEntity('Tests\UserConstructor', $array);

		$this->assertInstanceOf('Tests\UserConstructor', $entity);
		$this->assertSame(1, $entity->getId());
		$this->assertInstanceOf('Tests\Role', $entity->getRole());
		$this->assertNull($entity->getOptionalRole());
		$this->assertSame('name', $entity->getName()); // Default value in constructor
	}

	public function testFind() {
		$array = array(
			'id' => 1,
			'role' => 1,
			'items' => [
				1, 2, 3
			]
		);

		$settings = new \WebChemistry\Forms\Doctrine\Settings();
		$settings->setFind(array(
			'role', 'items'
		));
		/** @var \Tests\User $entity */
		$entity = $this->helper->toEntity('Tests\User', $array, $settings);

		$this->assertEquals([
			'Tests\Role' => [ 1 ],
			'Tests\Item' => [ 1, 2, 3]
		], \Tests\DefaultRepository::$find);
		$this->assertNotNull($entity->getRole());
		$this->assertInstanceOf('Tests\Role', $entity->getRole());
		$this->assertSame(3, $entity->getItems()->count());
		foreach ($entity->getItems() as $item) {
			$this->assertInstanceOf('Tests\Item', $item);
		}
	}
	
	public function testIteratorToArray() {
		$arr = [
			'id' => 1,
			'name' => 'foo',
			'role' => [
				'id' => 1
			]
		];
		$arr = \Nette\Utils\ArrayHash::from($arr);

		$entity = $this->helper->toEntity('Tests\User', $arr);
		
		$this->assertSame(1, $entity->getId());
		$this->assertSame(1, $entity->getRole()->getId());
	}

	public function testCascade() {
		$arr = [
			'users' => [
				['id' => 1],
				['id' => 2]
			]
		];

		$entity = $this->helper->toEntity('Tests\Role', $arr);
		$this->assertSame(2, $entity->getUsers()->count());
		$this->assertSame(1, $entity->getUsers()[0]->getId());
		$this->assertSame(2, $entity->getUsers()[1]->getId());
	}

}
