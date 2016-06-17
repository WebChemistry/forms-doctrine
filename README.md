# Rozšíření pro WebChemistry\Forms\Form

[![Build Status](https://travis-ci.org/WebChemistry/Forms-Doctrine.svg?branch=master)](https://travis-ci.org/WebChemistry/Forms-Doctrine)

## Instalace

**Composer**

```
composer require webchemistry/forms-doctrine
```

**Config**

```yaml
extensions:
    - WebChemistry\Forms\Doctrine
```

## Použití ve formulářu

```php

/** @var WebChemistry\Forms\Doctrine @inject */
public $doctrine;

protected function createComponentForm() {
    $form = new WebChemistry\Forms\Form;
    
    $form->setDoctrine($this->doctrine); // Při použití provideru nemusíme nastavovat
    
    $form->addText('name', 'Uživatelské jméno')
         ->setRequired();

    $form->addText('password', 'Heslo')
         ->setRequired();

    $form->addCheckbox('remember', 'Zůstat přihlášen');

    $form->addSubmit('submit', 'Přihlásit');

    $form->setEntity($this->em->getRepository('Entity\User')->find(1));

    return $form;
}

public function afterSign(WebChemistry\Forms\Application\Form $form) {
    $entity = $form->getEntity(); // Bere hodnoty z $form->setEntity() a doplní je o nové

    $entity = $form->getEntity('Entity\User'); // Vytvoří novou třídu Entity\User a vyplní

    $e = $this->em->getRepository('Entity\User')->find(2);

    $entity = $form->getEntity($e); // Doplní třídu o nové hodnoty
}

```

## Export vybraných položek

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setAllowedItems(array(
       'name', // Vybere položku name,
       'cart' => array('*'), // Vybere všechny položky v cart
       'history' => array('id') // Vybere položku id v history
    ));
   
    $this->doctrine->toArray($this->entity, $settings);
}
```

## Export jedné položky v join

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setJoinOneColumn(array(
        'role' => 'id'
    ));
   
    // Vytvoří pole === ['role' => 5]
   
    $this->doctrine->toArray($this->entity, $settings);
}
```

## Vlstní callback k položkám

```php
public function export() {
    $settings = new new WebChemistry\Forms\Doctrine\Settings();
    $settings->setCallbacks(array(
        'role' => function ($value, $baseEntity, &$continue) {
            return ['id' => $value->id];
        }
    ));
    
    // Vytvoří pole === ['role' => ['id' => 5]]
   
    $this->doctrine->toArray($this->entity, $settings);
}
```
