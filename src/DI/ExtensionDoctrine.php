<?php

namespace WebChemistry\Forms\DI;

use Nette\DI\CompilerExtension;

class DoctrineExtension extends CompilerExtension {

	/**
	 * Processes configuration data. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function loadConfiguration() {
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('doctrine'))
				->setClass('WebChemistry\Forms\Doctrine');
	}

}