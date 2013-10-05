<?php
/**
 * This file contains WP Events Calendar plugin.
 *
 * This is the main WPEC file.
 * @internal			Complete the description.
 *
 * @package			WP-Events-Calendar
 * @since			1.0
 * 
 * @autbor			Luke Howell <luke@wp-eventscalendar.com>, Ugoku <wordpress@ugoku.nl>
 *
 * @copyright			Copyright (c) 2007-2009 Luke Howell, 2013 Ugoku
 *
 * @license			GPLv3 {@link http://www.gnu.org/licenses/gpl}
 * @filesource
 */
/*
--------------------------------------------------------------------------

WP Events Calendar is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.   See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

if (!class_exists('EventsCalendar'))
{
	require_once(EVENTSCALENDARCLASSPATH . '/ec_widget.class.php');
	require_once(EVENTSCALENDARCLASSPATH . '/ec_management.class.php');

	class EventsCalendar
	{
		var $widget;
		var $management;

		function EventsCalendar()
		{
			$this->widget = new EC_Widget();
			$this->management = new EC_Management();
		}

		function displayWidget($args)
		{
			$this->widget->display($args);
		}

		function displayManagementPage()
		{
			$this->management->display();
		}

		function displayOptionsPage()
		{
			$this->management->calendarOptions();
		}
	}
}
?>