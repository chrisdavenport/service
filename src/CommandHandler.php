<?php
/**
 * @package     Joomla.Framework
 * @subpackage  Service Layer
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Service;

/**
 * Abstract base class for command handlers.
 * 
 * Supports handling of domain events.  This would be better implemented
 * as a trait, but traits are not implemented until PHP 5.4.0.
 * 
 * @since  __DEPLOY_VERSION__
 */
abstract class CommandHandler
{
	// Command bus.
	private $commandBus = null;

	// Domain events.
	private $pendingEvents = array();

	/**
	 * Constructor.
	 * 
	 * @param   CommandBus  $commandBus  A command bus.
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(CommandBus $commandBus)
	{
		$this->commandBus = $commandBus;
	}

	/**
	 * Get the command bus.
	 * 
	 * @return   CommandBus
	 */
	public function getCommandBus()
	{
		return $this->commandBus;
	}

	/**
	 * Raise a domain event.
	 * 
	 * @param   DomainEvent  $event  Domain event object.
	 * 
	 * @return  void
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function raiseEvent(DomainEvent $event)
	{
		$this->pendingEvents[] = $event;
	}

	/**
	 * Release all pending domain events.
	 * 
	 * As a convenience, a new event can also be raised at the same time.
	 * 
	 * @param   DomainEvent  $event  An event to be raised.
	 * 
	 * @return  array of DomainEvent objects.
	 * 
	 * @since   __DEPLOY_VERSION__
	 */
	public function releaseEvents($event = null)
	{
		if ($event instanceof DomainEvent)
		{
			$this->raiseEvent($event);
		}

		$events = $this->pendingEvents;
		$this->pendingEvents = array();

		return $events;
	}
}
