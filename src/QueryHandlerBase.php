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
 * Abstract base class for query/service handlers.
 * 
 * @since   __DEPLOY__
 */
abstract class QueryHandlerBase implements QueryHandler
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
		return false;
	}
}