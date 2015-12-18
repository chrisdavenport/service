<?php
/**
 * Tactician middleware for dispatching domain events.
 */

namespace Joomla\Service;

use League\Tactician\Middleware;

class DomainEventMiddleware implements Middleware
{
	protected $dispatcher = null;

	/**
	 * Constructor.
	 * 
	 * @param   EventDispatcher  $dispatcher  An event dispatcher.
	 */
	public function __construct(\JEventDispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}
	
	/**
	 * Decorator.
	 * 
	 * Calls the inner handler then dispatches any DomainEvents raised.
	 *
	 * Suppose there is a DomainEvent with the class name 'PrefixEventSuffix',
	 * then you can register listeners for the event using:-
	 *   1. A closure.  Example:
	 *          \JEventDispatcher::getInstance()->register('onPrefixEventSuffix', function($event) { echo 'Do something here'; });
	 *   2. A callback function or method.  Example:
	 *          \JEventDispatcher::getInstance()->register('onPrefixEventSuffix', array('MyClass', 'MyMethod'));
	 *   3. A preloaded or autoloadable class called 'PrefixEventListenerSuffix' with a method called 'onPrefixEventSuffix'.
	 *   4. An installed and enabled Joomla plugin in the 'domainevent' group, with a method called 'onPrefixEventSuffix'.
	 * In all cases the method called will be passed a single argument consisting of the event object.
	 * 
	 * @param   Command   $command  Command object.
	 * @param   callable  $next     Inner middleware object being decorated.
	 * 
	 * @return  void
	 */
	public function execute($command, callable $next)
	{
		// Execute the command.
		$events = $next($command);

		// Normally, we expect a possibly empty array of Domain Events.
		// but if we don't get an array, then bubble an empty array up.
		if (!is_array($events))
		{
			return array();
		}

		// Handle any domain events that were raised.
		foreach ($events as $event)
		{
			// Import plugins in the domain event group.
			\JPluginHelper::importPlugin('domainevent');

			// Get the name of the event.
			$eventClassName = (new \ReflectionClass($event))->getShortName();

			// Determine the event name.
			$eventName = 'on' . $eventClassName;

			// Register by convention.
			$this->registerByConvention($eventClassName, $eventName);

			// Publish the event to all registered listeners.
			$this->dispatcher->trigger($eventName, array($event));
		}

		// Continue bubbling the events up.
		return $events;
	}

	/**
	 * Register a domain event listener by convention.
	 * 
	 * Replaces "Event" by "EventListener" in the domain event class name
	 * and registers that class as a listener.
	 * 
	 * @param   string  $eventClassName  Name of the domain event class.
	 * @param   string  $eventName       Name of the event trigger.
	 * 
	 * @return  void
	 */
	private function registerByConvention($eventClassName, $eventName)
	{
		// The domain event class name must contain the substring "Event".
		if (stripos($eventClassName, 'event') === false)
		{
			return;
		}

		// Determine the event handler class name.
		$handlerClassName = '\\' . str_replace('Event', 'EventListener', $eventClassName);

		// If the event handler class exists, then register it.
		if (class_exists($handlerClassName))
		{
			$this->dispatcher->register($eventName, array($handlerClassName, $eventName));
		}
	}
}