<?php
/**
 * RedBean Dependency Injector
 *
 * @file    RedBean/DependencyInjector.php
 * @desc    Simple dependency injector
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * A default dependency injector that can be subclassed to
 * suit your needs. This injector can be used to inject helper objects into
 * FUSE(d) models.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_DependencyInjector
{

	/**
	 * @var array
	 */
	protected $dependencies = array();

	/**
	 * Adds a dependency to the list.
	 * You can add dependencies using this method. Pass both the key of the
	 * dependency and the dependency itself. The key of the dependency is a
	 * name that should match the setter. For instance if you have a dependency
	 * class called My_Mailer and a setter on the model called setMailSystem
	 * you should pass an instance of My_Mailer with key MailSystem.
	 * The injector will now look for a setter called setMailSystem.
	 *
	 * @param string $dependencyID name of the dependency (should match setter)
	 * @param mixed  $dependency   the service to be injected
	 *
	 * @return void
	 */
	public function addDependency( $dependencyID, $dependency )
	{
		$this->dependencies[$dependencyID] = $dependency;
	}

	/**
	 * Returns an instance of the class $modelClassName completely
	 * configured as far as possible with all the available
	 * service objects in the dependency list.
	 *
	 * @param string $modelClassName the name of the class of the model
	 *
	 * @return mixed
	 */
	public function getInstance( $modelClassName )
	{
		$object = new $modelClassName;

		if ( $this->dependencies && is_array( $this->dependencies ) ) {
			foreach ( $this->dependencies as $key => $dep ) {
				$depSetter = 'set' . $key;

				if ( method_exists( $object, $depSetter ) ) {
					$object->$depSetter( $dep );
				}
			}
		}

		return $object;
	}
}
