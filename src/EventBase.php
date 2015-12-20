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
 * Abstract base class for immutable domain events.
 * 
 * Usage
 *   Events are immutable objects that are completely defined by the arguments
 *   passed to them in their constructors.  Some basic checks are performed to
 *   try to enforce immutability, but these only really guard against accidental
 *   alteration of object state.
 * 
 * @since  __DEPLOY__
 */
abstract class EventBase extends Immutable implements Event
{
}
