<?php
/**
 * Service Layer event dispatcher.
 * 
 * Dispatches domain events raised by models by publishing them to registered observers.
 */

namespace Joomla\Service;

class EventDispatcher
{
	/**
	 * Dispatch domain events.
	 * 
	 * @param   array  $events  Array of DomainEvent objects to be handled.
	 */
	public function dispatch(array $events)
	{
		print_r($events);
		exit(__METHOD__);

//		JLoader::discover('EventHandler', JPATH_COMPONENT . '/eventhandlers/');

		foreach ($events as $event)
		{
//			$classname = 'EventHandler' . ucfirst($event->getEventName());
//
//			if (!class_exists($classname))
//			{
//				Diag::mail(DIAG_EMAIL, __METHOD__, 'Domain event handler not found', $event, $classname);
//
//				continue;
//			}
//
//			$handler = new $classname;
//			$handler->handle($event);
		}
	}
}