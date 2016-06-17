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

	public function testBase() {
		$role = new \Tests\Role();
		$role->setId('id');
		$role->setName('name');

		$this->assertEquals([
			'id' => 'id',
			'name' => 'name',
			'public' => NULL
		], $this->helper->toArray($role));
	}

	public function testAssociation() {
		$user = new \Tests\User();
		$user->setId(1);
		$role = new \Tests\Role();
		$role->setId(1);
		$user->setRole($role);

		$this->assertEquals([
			'id' => 1,
			'role' => [
				'id' => 1,
				'name' => NULL,
				'public' => NULL
			],
			'items' => [],
			'notice' => NULL
		], $this->helper->toArray($user));
	}

	public function testManyToMany() {
		$user = new \Tests\User();
		$item = new \Tests\Item();
		$item->setId(1);
		$user->addItem($item);
		$item = new \Tests\Item();
		$item->setId(2);
		$user->addItem($item);

		$this->assertEquals([
			'items' => [
				['id' => 1],
				['id' => 2]
			],
			'id' => NULL,
			'role' => NULL,
			'notice' => NULL
		], $this->helper->toArray($user));
	}

	public function testOneToOne() {
		$user = new \Tests\User();
		$notice = new \Tests\Notice();
		$notice->setId(1);
		$user->setNotice($notice);

		$this->assertEquals([
			'notice' => [
				'id' => 1
			],
			'id' => NULL,
			'items' => [],
			'role' => NULL
		], $this->helper->toArray($user));
	}

}
