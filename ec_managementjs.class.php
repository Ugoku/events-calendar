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

if (!class_exists('EC_ManagementJS'))
{
	require_once (EVENTSCALENDARCLASSPATH.'/ec_db.class.php');

/**
 * Dashboard calendar management content.
 *
 * @package WP-Events-Calendar
 */
class EC_ManagementJS
{

	var $db;

	function EC_ManagementJS()
	{
		$this->db = new EC_DB();
	}

	/**
	 * Outputs the data for each day that has an event.
	 *
	 * The class provides the data and JS necessary to produce
	 * tooltips and data management such as deletes and edits.
	 *
	 * @param int $m 		month
	 * @param int $y 		year
	 */
	function calendarData($m, $y)
	{
		$options = get_option('optionsEventsCalendar');
		$lastDay = date('t', mktime(0, 0, 0, $m, 1, $y));
		for ($d = 1; $d <= $lastDay; $d++)
		{
     	$sqldate = date('Y-m-d', mktime(0, 0, 0, $m, $d, $y));
			foreach ($this->db->getDaysEvents($sqldate) as $e)
			{
				$output = '';
				$id = "$d-$e->id";
				$title = $e->eventTitle;
				$description = preg_replace('#\r?\n#', '<br>', $e->eventDescription);
				$location = isset ($e->eventLocation) ? $e->eventLocation : '';
				$linkout = isset ($e->eventLinkout) ? $e->eventLinkout : '';
				$startDate = $e->eventStartDate;
				$endDate = $e->eventEndDate;
				$startTime = isset ($e->eventStartTime) ? $e->eventStartTime : '';
				$endTime = isset ($e->eventEndTime) ? $e->eventEndTime : '';
				$accessLevel = $e->accessLevel;
				$PostID = isset ($e->postID) ? $e->postID : '';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Title','events-calendar') . ': ' . $title . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Location','events-calendar') . ': ' . $location . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Description','events-calendar') . ': ' . $description . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Start Date','events-calendar') . ': ' . $startDate . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Start Time','events-calendar') . ': ' . $startTime . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('End Date','events-calendar') . ': ' . $endDate . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('End Time','events-calendar') . ': ' . $endTime . '</p>';
				$output .= '<p class="ec-mgmt-ttip">' . _x('Visibility','events-calendar') . ': ' . $accessLevel . '</p>';
				$asslink = '';
				if ($linkout !== '')
				{
					$output .= '<p class="ec-mgmt-ttip">'._x('Link out','events-calendar')." :".substr($linkout,0,19)."</p>";
					$asslink = '<img id=\"events-calendar-link-' . $d . '-' . $e->id . '\" src=\"' . EVENTSCALENDARIMAGESURL . '/link.png\" style=\"width:10px;height:10px;\" title=\"' . __("Associated link","events-calendar") . '\">&nbsp;';
				}
				$asspost = '';
				if ($PostID !== '')
				{
					$IDtmp = get_post($PostID);
					$ptitle = $IDtmp->post_title;
					//TODO: Check if $ptitle = get_the_title ($PostID); works
					$output .= '<p class="ec-mgmt-ttip">'._x('Post','events-calendar')." ($PostID) : $ptitle.</p>";
					$asspost = '<img id=\"events-calendar-post-' . $d . '-' . $e->id . '\" src=\"' . EVENTSCALENDARIMAGESURL . '/post.gif\" style=\"width:10px;height:10px;\" title=\"' . __("Associated post","events-calendar") . '\">&nbsp;';
				}

				if ($output != '')
				{
					// make sure we don't double escape
					if (preg_match("/\'|\"/", $output))
						$output = stripslashes($output);
?>
<script>

(function($) {
	$('#events-calendar-<?php echo $d;?>').append("<div id=\"events-calendar-container-<?php echo $id;?>\"><?php echo $asslink, $asspost;?><span id=\"events-calendar-<?php echo $id;?>\"><?php echo $title;?>&nbsp;</span><img id=\"events-calendar-delete-<?php echo $id;?>\" src=\"<?php echo EVENTSCALENDARIMAGESURL;?>/delete.png\" style=\"width:12px;height:12px;\" title=\"<?php _e('Delete','events-calendar');?>\"><\div>");
	$('#events-calendar-<?php echo $id;?>')
		.attr('title', '<?php echo addslashes($output);?>')
		.css('color', 'black')
		.css('font-size', '0.9em')
		.mouseover(function() {
			$(this).css('cursor', 'pointer');
		})
		.click(function() {
			top.location = "?page=events-calendar&EC_action=edit&EC_id=<?php echo $e->id;?>";
		})
		.tooltip({
			delay:0,
			track:true
		});
	$('#events-calendar-link-<?php echo $id;?>')
		.mouseover(function() {
			$(this).css('cursor', 'pointer');
		})
		.click(function() {
			window.open('<?php echo $linkout;?>');
		});
	$('#events-calendar-post-<?php echo $id;?>')
		.mouseover(function() {
			$(this).css('cursor', 'pointer');
		})
		.click(function() {
			window.open('<?php echo get_permalink($PostID);?>');
		});
	$('#events-calendar-delete-<?php echo $id;?>')
		.mouseover(function() {
			$(this).css('cursor', 'pointer');
		 })
		.click(function() {
			doDelete = confirm("<?php _e('Are you sure you want to delete the following event:\n','events-calendar');echo $e->eventTitle;?>");
			if (doDelete) {
				$.get("<?php bloginfo('siteurl');?>/wp-admin/admin.php?page=events-calendar",
					{EC_action: "ajaxDelete", EC_id: <?php echo $e->id;?>},
					function(data) {
						for(d = 1; d <= <?php echo $lastDay;?>; d++) {
							$('#events-calendar-container-' + d + '-<?php echo $e->id;?>')
								.css('background', 'red')
								.fadeOut(1000);
						}
					}
				);
			}
		});
})(jQuery);
</script>
<?php
				}
			}
		}
		$this->calendarjs();
	}


	/**
	 * provides the javascript for the date and time pickers.
	 */
	function calendarjs()
	{
		global $loc_lang;
		$options = get_option('optionsEventsCalendar');
		if (false === stripos($options['timeFormatWidget'], 'a'))
			$military = 'true';
		else
			$military = 'false';
?><?php /*
<script>
//jQuery.noConflict();
(function($) {
	 $("#EC_startDate").datepicker($.extend({},
			$.datepicker.regional["<?php echo $loc_lang; ?>"], {
				showOn: "button",
				showStatus: true,
				buttonImage: "<?php echo EVENTSCALENDARIMAGESURL."/calendar.gif";?>",
				buttonImageOnly: true,
				dateFormat: 'yy-mm-dd',
				firstDay: <?php echo get_option('start_of_week');?>
		  }
	 ));
	 $("#EC_endDate").datepicker($.extend({},
			$.datepicker.regional["<?php echo $loc_lang; ?>"], {
				showOn: "button",
				showStatus: true,
				buttonImage: "<?php echo EVENTSCALENDARIMAGESURL."/calendar.gif";?>",
				buttonImageOnly: true,
				dateFormat: 'yy-mm-dd',
				firstDay: <?php echo get_option('start_of_week');?>
		  }
	 ));

	$("#EC_start_clockpick").clockpick({
		military: <?php echo $military; ?>,
		  useBgiframe: true,
		  valuefield: 'EC_startTime',
		  starthour: '0',
		  endhour: '23',
		  layout: 'horizontal'
	 });
	 $("#EC_end_clockpick").clockpick({
		military: <?php echo $military; ?>,
		  useBgiframe: true,
		  valuefield: 'EC_endTime',
		  starthour: '0',
		  endhour: '23',
		  layout: 'horizontal'
	 });
})(jQuery);
</script>*/?>
<?php
  }
}
}
?>