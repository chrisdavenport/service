<?php
/**
 * @package     Joomla.Framework
 * @subpackage  Service Layer
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Service;

/**
 * Base class for command/service handlers.
 * 
 * Supports handling of domain events.  This would be better implemented
 * as a trait, but traits are not implemented until PHP 5.4.0.
 * 
 * @since   __DEPLOY__
 */
class CommandHandlerBase implements CommandHandler
{
	// Domain events.
	private $pendingEvents = array();

	/**
	 * Raise a domain event.
	 * 
	 * @param   DomainEvent  $event  Domain event object.
	 * 
	 * @return  void
	 */
	public function raiseEvent(Event $event)
	{
		$this->pendingEvents[] = $event;
	}

	/**
	 * Release all pending domain events.
	 * 
	 * @return  array of DomainEvent objects.
	 */
	public function releaseEvents()
	{
		$events = $this->pendingEvents;
		$this->pendingEvents = array();

		return $events;
	}

	/**
	 * Handle a command.
	 * 
	 * @param   Command  $command  A command object.
	 * 
	 * @return  mixed
	 * @since   __DEPLOY__
	 */
	public function handle(Command $command)
	{
		return false;
	}
}