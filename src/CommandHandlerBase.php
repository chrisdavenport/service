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
 * Abstract base class for command/service handlers.
 * 
 * @since   __DEPLOY__
 */
abstract class CommandHandlerBase implements CommandHandler
{
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