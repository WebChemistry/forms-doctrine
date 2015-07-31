# Rozšíření pro WebChemistry\Forms\Form

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
    $exclude = array(
        '*', // Vybere všechny položky 
        '~name', // Vynechá položku name
        'cart' => array('~id') // Vynechá položku id v cart
    );

    $this->doctrine->toArray($this->entity, $exclude);
    
    $include = array(
        'name', // Vybere položku name,
        'cart' => array('*'), // Vybere všechny položky v cart
        'history' => array('id') // Vybere položku id v history
    );
}
```