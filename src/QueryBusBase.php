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
 * Query Bus proxy class.
 * 
 * This is just a proxy to the actual query bus implementation.
 * The League Tactician command bus currently proxied requires PHP 5.5 minimum
 * and so cannot be used across all Joomla 3.x sites.  This needs to be
 * resolved before release.
 * 
 * @since   __DEPLOY__
 */
class QueryBusBase extends \League\Tactician\CommandBus implements QueryBus
{
	/**
	 * Handle a query.
	 * 
	 * @param   Query  $query  A query object.
	 * 
	 * @return  mixed
	 * @since   __DEPLOY__
	 */
	public function handle(Query $query)
	{
		return parent::handle($query);
	}
}