<?php
/**
 * Example of phpQuery plugin.
 *
 * Load it like this:
 * phpQuery::plugin('example')
 * phpQuery::plugin('example', 'example.php')
 * pq('ul')->plugin('example')
 * pq('ul')->plugin('example', 'example.php')
 *
 * Plugin classes are never intialized, just method calls are forwarded
 * in static way from phpQuery.
 *
 * Have fun writing plugins :)
 */

/**
 * phpQuery plugin class extending phpQuery object.
 * Methods from this class are callable on every phpQuery object.
 *
 * Class name prefix 'phpQueryObjectPlugin_' must be preserved.
 */
abstract class phpQueryObjectPlugin_example {
	/**
	 * Limit binded methods.
	 *
	 * null means all public.
	 * array means only specified ones.
	 *
	 * @var array|null
	 */
	public static $phpQueryMethods = null;
	/**
	 * Enter description here...
	 *
	 * @param phpQueryObject $self
	 */
	public static function example($self, $arg1) {
		// this method can be called on any phpQuery object, like this:
		// pq('div')->example('$arg1 Value')

		// do something
		$self->append('Im just an example !');
		// change stack of result object
		return $self->find('div');
	}
	protected static function helperFunction() {
		// this method WONT be avaible as phpQuery method,
		// because it isn't publicly callable
	}
}

/**
 * phpQuery plugin class extending phpQuery static namespace.
 * Methods from this class are callable as follows:
 * phpQuery::$plugins->staticMethod()
 *
 * Class name prefix 'phpQueryPlugin_' must be preserved.
 */
abstract class phpQueryPlugin_example {
	/**
	 * Limit binded methods.
	 *
	 * null means all public.
	 * array means only specified ones.
	 *
	 * @var array|null
	 */
	public static $phpQueryMethods = null;
	public static function staticMethod() {
		// this method can be called within phpQuery class namespace, like this:
		// phpQuery::$plugins->staticMethod()
	}
}
?>