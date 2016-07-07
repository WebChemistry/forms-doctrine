# Entity to array and conversely

[![Build Status](https://travis-ci.org/WebChemistry/forms-doctrine.svg?branch=master)](https://travis-ci.org/WebChemistry/forms-doctrine)

## Installation

```yaml
extensions:
    - WebChemistry\Forms\DoctrineExtension
```

## Usage

Entity:
```php
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

	public function __construct($id) {
		$this->items = new ArrayCollection();
		$this->setId($id);
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
```

```php

$values = [
	'id' => 5,
	'role' => [
		'id' => 1,
		'name' => 2
	],
	'items' => [
		['id' => 1] // Calls addItem() for each item
		['id' => 2]
	]
];
/** @var Entity\User $entity */
$entity = $this->helper->toEntity('Entity\User', $values);


$array = $this->helper->toArray($entity);

var_dump($array == $entity); // dumps true
```

## Export selected items

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setAllowedItems([
       'name', // Select name
       'items' => ['*'], // Select all items in items
       'role' => array('id') // Select id in role
    ]);

    $this->doctrine->toArray($this->entity, $settings);
}
```

## Export one item in sub entities

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setJoinOneColumn(array(
        'role' => 'id'
    ));

    // Create array: ['role' => 5] instead of ['role' => ['id' => 5, 'name' => 'foo']]

    $this->doctrine->toArray($this->entity, $settings);
}
```

## Custom callback

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setCallbacks(array(
        'role' => function ($value, $entity) {
            return ['id' => $value->getId() * 2];
        }
    ));

    // Create array ['role' => ['id' => 10]] instead of ['role' => ['id' => 5, 'name' => 'foo']]

    $this->doctrine->toArray($this->entity, $settings);
}
```

## Auto-find by ID

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setFind([
        'role' => 10 // Uses method find from repository
    ]);

    $this->doctrine->toArray($this->entity, $settings);
}
```

## Usage in forms

We must install [webchemistry/forms](https://github.com/WebChemistry/forms)

```php

/** @var WebChemistry\Forms\Factory\FormFactory @inject */
public $factory;

protected function createComponentForm() {
    $form = $this->factory->create();
    
    $form->addText('name', 'User name')
         ->setRequired();

    $form->addText('password', 'Password')
         ->setRequired();

    $form->addCheckbox('remember', 'Remember');

    $form->addSubmit('submit', 'Sign in');

    $form->setEntity($this->em->getRepository('Entity\User')->find(1));

    return $form;
}

public function afterSign(WebChemistry\Forms\Application\Form $form) {
    $entity = $form->getEntity(); // Gets object from set object and fill it with new values
    $entity = $form->getEntity('Entity\User'); // Create new class
}

```

