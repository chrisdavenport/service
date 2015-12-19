<?php
/**
 * Tactician middleware for dispatching domain events.
 */

namespace Joomla\Service;

use Joomla\DI\Container;
use League\Tactician\Middleware;

class DomainEventMiddleware implements Middleware
{
	/**
	 * Dependency injection container.
	 */
	protected $container = null;

	/**
	 * Event dispatcher.
	 */
	protected $dispatcher = null;

	/**
	 * Constructor.
	 * 
	 * @param   Container        $container   A dependency injection container.
	 * @param   EventDispatcher  $dispatcher  An event dispatcher.
	 */
	public function __construct(Container $container, \JEventDispatcher $dispatcher)
	{
		$this->container = $container;
		$this->dispatcher = $dispatcher;
	}
	
	/**
	 * Decorator.
	 * 
	 * Calls the inner handler then dispatches any domain events raised.
	 *
	 * Suppose there is a DomainEvent with the class name 'PrefixEventSuffix',
	 * then you can register listeners for the event using:-
	 *   1. A closure.  Example:
	 *          \JEventDispatcher::getInstance()->register('onPrefixEventSuffix', function($event) { echo 'Do something here'; });
	 *   2. A callback function or method.  Example:
	 *          \JEventDispatcher::getInstance()->register('onPrefixEventSuffix', array('MyClass', 'MyMethod'));
	 *   3. A preloaded or autoloadable class called 'PrefixEventListenerSuffix' with a method called 'onPrefixEventSuffix'.
	 *   4. An installed and enabled Joomla plugin in the 'domainevent' group, with a method called 'onPrefixEventSuffix'.
	 * 
	 * In all cases the method called will be passed two arguments: the event object and the dependency injection container.
	 * 
	 * @param   Command   $command  Command object.
	 * @param   callable  $next     Inner middleware object being decorated.
	 * 
	 * @return  void
	 */
	public function execute($command, callable $next)
	{
		$accumulatedEvents = array();

		// Pass the command to the next inner layer of middleware.
		$events = $next($command);

		// Normally, we expect a possibly empty array of events,
		// but if we don't get an array, then bubble an empty array up.
		if (!is_array($events))
		{
			return $accumulatedEvents;
		}

		// Recursively publish any domain events that were raised.
		do
		{
			// Accumulate all events raised.
			$accumulatedEvents = array_merge($accumulatedEvents, $events);

			// Publish the events.
			$events = $this->innerEventLoop($events);
		}
		while (!empty($events));

		// Bubble the events up to the next outer layer of middleware.
		return $accumulatedEvents;
	}

	/**
	 * Inner event loop.
	 * 
	 * Each event listener might raise further events which need
	 * to be passed back into the event loop for publishing.
	 * 
	 * @param   array  $events  Array of domain event objects.
	 * 
	 * @return  array of newly-raised domain event objects.
	 */
	private function innerEventLoop($events)
	{
		$collectedEvents = array();

		foreach ($events as $event)
		{
			// Ignore anything that isn't actually an event, just in case.
//			if (!($event instanceof Event))
//			{
//				continue;
//			}

			// Import plugins in the domain event group.
			\JPluginHelper::importPlugin('domainevent');

			// Get the name of the event.
			$eventClassReflection = new \ReflectionClass($event);
			$eventClassName = $eventClassReflection->getShortName();

			// Determine the event name.
			$eventName = 'on' . $eventClassName;

			// Register by convention.
			$this->registerByConvention($eventClassName, $eventName);

			// Publish the event to all registered listeners.
			$results = $this->dispatcher->trigger($eventName, array($event, $this->container));

			// Merge results into collected events array.
			foreach ($results as $result)
			{
				$collectedEvents = array_merge($collectedEvents, $result);
			}
		}

		return $collectedEvents;
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