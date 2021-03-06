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

if (!class_exists('EC_Management'))
{
	require_once(EVENTSCALENDARCLASSPATH . '/ec_calendar.class.php');
	require_once(EVENTSCALENDARCLASSPATH . '/ec_db.class.php');
	require_once(EVENTSCALENDARCLASSPATH . '/ec_managementjs.class.php');

/**
 * Dashboard management.
 *
 * Enables users to add/edit/delete events and control the widget.
 *
 * @package WP-Events-Calendar
 * @since   6.0  
 */
class EC_Management
{

	/**
	 * Month to manage.
	 * @var int
	 * @access private
	 */
	var $month;

	/**
	 * Year to manage.
	 * @var int
	 * @access private
	 */
	var $year;

	/**
	 * Holds the http protocol string.
	 * @var string
	 * @access private
	 */
	var $deflinkout;

	/**
	 * Holds the EC_Calendar object.
	 * @var object
	 * @access private
	 */
	var $calendar;

	/**
	 * Holds the EC_DB object.
	 * @var Object
	 * @access private
	 */
	var $db;

  /**
	* Constructor.
	*/
	function __construct()
	{
		$this->month = date('m');
		$this->year = date('Y');
		
		if (isset($_GET['EC_action']))
		{
			$this->month = $_GET['EC_action'] == 'switchMonthAdmin' ? $_GET['EC_month'] : date('m');
			$this->year = $_GET['EC_action'] == 'switchMonthAdmin' ? $_GET['EC_year'] : date('Y');
		}

		$this->deflinkout = ''; // 'http://';
		$this->calendar = new EC_Calendar();
		$this->db = new EC_DB();
	}

	/**
	 * Displays the admin calendar and Add Event form.
	 *
	 * If the form was submitted, adds or updates the event in database.
	 *
	 * @use EC_ManagementJS
	 */
	function display()
	{
		global $wpdb, $current_user;
		$js = new EC_ManagementJS();

		// adds a new event to database
		if (isset ($_POST['EC_addEventFormSubmitted']))
		{
			// all the strings are escaped. 
			$title = $_POST['EC_title'];
			$location = isset($_POST['EC_location']) && !empty($_POST['EC_location']) ? $_POST['EC_location'] : null;
			$linkout = isset($_POST['EC_linkout']) && !empty($_POST['EC_linkout']) && ($_POST['EC_linkout'] != $this->deflinkout) ? $_POST['EC_linkout'] : null;
			$description = $_POST['EC_description'];
			$startDate = isset($_POST['EC_startDate']) && !empty($_POST['EC_startDate'])? $_POST['EC_startDate'] : date('Y-m-d');
			$startTime = isset($_POST['EC_startTime']) && !empty($_POST['EC_startTime']) ? $_POST['EC_startTime'] : null;
			$endDate = isset($_POST['EC_endDate']) && !empty($_POST['EC_endDate']) ? $_POST['EC_endDate'] : $startDate;
			$endDate = strcmp($startDate, $endDate) > 0 ? $startDate : $endDate;
			$endTime = isset($_POST['EC_endTime']) && !empty($_POST['EC_endTime']) ? $_POST['EC_endTime'] : null;
			$accessLevel = $_POST['EC_accessLevel'];

			$output = '<strong>' . _x('Title','events-calendar') . ': </strong>' . $title . '<br>';

			if (!empty($location) && !is_null($location))
				$output .= '<strong>' . _x('Location','events-calendar').': </strong>' . $location . '<br>';
			if (!empty($linkout) && !is_null($linkout))
				$output .= '<strong>' . _x('Link out','events-calendar').': </strong><a href="' . $linkout . '" target="_blank">' . _x('Click here','events-calendar') . '</a><br>';
			if (!empty($description) && !is_null($description))
				$output .= '<strong>' . _x('Description','events-calendar').': </strong>' . $description . '<br>';
			if ($startDate != $endDate )
				$output .= '<strong>'._x('Start Date','events-calendar').': </strong>' . $startDate . '<br>';
			if (!empty($startTime) || !is_null($startTime))
				$output .= '<strong>'._x('Start Time','events-calendar').': </strong>' . $startTime . '<br>';
			if ($startDate != $endDate)
				$output .= '<strong>'._x('End Date','events-calendar').': </strong>' . $endDate . '<br>';
			if ($startDate == $endDate)
				$output .= '<strong>'._x('Date','events-calendar').': </strong>' . $startDate . '<br>';
			if (!empty($endTime) && !empty($startTime) || !is_null($endTime) && !is_null($startTime))
				$output .= '<strong>'._x('End Time','events-calendar').': </strong>' . $endTime . '<br>';

			$post_id = null;

			// do we have to insert a post?
			if (isset ($_POST['EC_doPost']))
			{
				$statusPost = $_POST['EC_statusPost'];

				// FIXME $this->blog_post_author is not defined anywhere
				//       why is it here?
			  if (isset ($this->blog_post_author) && !empty ($this->blog_post_author))
				  $post_author = $this->blog_post_author;
			  else
				  $post_author = $current_user->data->ID;

			  $data = array(
					'post_content' => stripslashes($output),
				 	'post_title' => stripslashes($title),
				 	'post_date' => date('Y-m-d H:i:s'),
				 	'post_category' => $post_author,
				 	'post_status' => $statusPost,
				 	'post_author' => $post_author,
			  );
				$postID = wp_insert_post($data);
			}

			$this->addEvent ($title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID);

			$splitDate = explode ("-", $startDate);
			$this->month = $splitDate[1];
			$this->year = $splitDate[0];
		}
	 
		if (isset ($_POST['EC_editEventFormSubmitted']))
		{
			$id = $_POST['EC_id'];

			$title = $_POST['EC_title'];
			$location = isset($_POST['EC_location']) && !empty($_POST['EC_location']) ? $_POST['EC_location'] : null;
			$linkout = isset($_POST['EC_linkout']) && !empty($_POST['EC_linkout']) && ($_POST['EC_linkout'] != $this->deflinkout) ? $_POST['EC_linkout'] : null;
			$description = $_POST['EC_description'];

			$startDate = isset($_POST['EC_startDate']) && !empty($_POST['EC_startDate'])? $_POST['EC_startDate'] : date('Y-m-d');
			$startTime = isset($_POST['EC_startTime']) && !empty($_POST['EC_startTime']) ? $_POST['EC_startTime'] : null;
			$endDate = isset($_POST['EC_endDate']) && !empty($_POST['EC_endDate']) ? $_POST['EC_endDate'] : $startDate;
			$endDate = strcmp($startDate, $endDate) >= 0 ? $startDate : $endDate;
			$endTime = isset($_POST['EC_endTime']) && !empty($_POST['EC_endTime']) ? $_POST['EC_endTime'] : null;
			$accessLevel = $_POST['EC_accessLevel'];
			$postID = isset($_POST['EC_postID']) && !empty($_POST['EC_postID']) ? $_POST['EC_postID'] : null;
			$this->editEvent($id, $title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID);
			$splitDate = explode ("-", $startDate);
			$this->month = $splitDate[1];
			$this->year = $splitDate[0];
		}

		// if user wants to edit an event, displays the form with data.
		if (isset ($_GET['EC_action']) && $_GET['EC_action'] == 'edit')
		{
			$this->editEventForm($_GET['EC_id']);
			// $js->calendarData($this->month, $this->year);
			$js->calendarjs();
		} else {
			// otherwise, just show the calendar and the Add Event form
			$this->calendar->displayAdmin($this->year, $this->month);
			$js->calendarData($this->month, $this->year);
			$this->addEventForm();
		}
	}

	/**
	 * Adds a new event to the database.
	 *
	 * @param string $title    title of the event.
	 * @param string $location  location of the event.
	 * @param string $linkout  either a user provided URL or a link to the
	 *        associated post if a post was published.
	 * @param $description
	 * @param string $startDate  starting date of the event.
	 * @param string $startTime  starting time of the event. Optional.
	 * @param string $endDate  ending date of the event. If not provided, ewill be
	 *        the same as starting date.
	 * @param string $endTime  ending time of the event.
	 * @param int $accessLevel  who can access this event.
	 * @param int $postID  associated post id if available.
	 */
	function addEvent($title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID)
	{
		$this->db->addEvent($title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID);
		return;
	}

	/**
	 * Updates an already existing event in the database.
	 *
	 * @param $id
	 * @param string $title    title of the event.
	 * @param string $location  location of the event.
	 * @param string $linkout  either a user provided URL or a link to the
	 *        associated post if a post was published.
	 * @param $description
	 * @param string $startDate  starting date of the event.
	 * @param string $startTime  starting time of the event. Optional.
	 * @param string $endDate  ending date of the event. If not provided, ewill be
	 *        the same as starting date.
	 * @param string $endTime  ending time of the event.
	 * @param int $accessLevel  who can access this event.
	 * @param int $postID  associated post id if available.
	 */
	function editEvent($id, $title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID)
	{
		$this->db->editEvent($id, $title, $location, $linkout, $description, $startDate, $startTime, $endDate, $endTime, $accessLevel, $postID);
	}

	/**
	 * Outputs the Add Event form.
	 *
	 * Provides the HTML and Javascript necessary for the user to add and validate a new event.
	 */
	function addEventForm()
	{
?>
	<h2 id="addEventform"><?php _e('Add Event','events-calendar'); ?></h2>
    <form name="EC_addEventForm" method="post" action="?page=events-calendar" onSubmit="return valid_addEventForm();" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>
    <div id="EC_alertmsg" class="alertmsg">
      <img id="EC_close_message_alert" src="<?php echo EVENTSCALENDARIMAGESURL; ?>/cross.png">
      <img id="ec-alert-img" src="<?php echo EVENTSCALENDARIMAGESURL; ?>/alert.png"> <strong><?php _e('Warning','events-calendar'); ?></strong>
      <p>message</p>
    </div>
      <table id="EC_management-add-form" summary="Event Add Form" class="ec-edit-form">
        <tr>
          <th scope="row"><label for="EC_title"><?php _e('Title','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_title" id="EC_title" required></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_location"><?php _e('Location','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_location" id="EC_location"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_linkout"><?php _e('Link out','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_linkout" id="EC_linkout" value="<?php echo $this->deflinkout;?>"></td>
        </tr>
        <tr>
          <th scope="row" valign="top"><label for="EC_description"><?php _e('Description','events-calendar'); ?></label></th>
          <td><textarea class="ec-edit-form-textarea" name="EC_description" id="EC_description"></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_startDate"><?php _e('Start Date (YYYY-MM-DD, if blank will be today)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-date" autocomplete="off" type="text" name="EC_startDate" id="EC_startDate" maxlength=10><img src="<?php echo EVENTSCALENDARIMAGESURL; ?>/calendar.gif"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_startTime"><?php _e('Start Time (HH:MM, can be blank)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-time" autocomplete="off" type="text" name="EC_startTime" id="EC_startTime" maxlength=5><img src="<?php echo EVENTSCALENDARIMAGESURL; ?>/time.png"><?php /*<img src="<?php echo EVENTSCALENDARIMAGESURL."/time.png";?>" id="EC_start_clockpick" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>*/?></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_endDate"><?php _e('End Date (YYYY-MM-DD, if blank will be same as start date)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-date" autocomplete="off" type="text" name="EC_endDate" id="EC_endDate" maxlength=10><img src="<?php echo EVENTSCALENDARIMAGESURL; ?>/calendar.gif"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_endTime"><?php _e('End Time (HH:MM, can be blank)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-time" autocomplete="off" type="text" name="EC_endTime" id="EC_endTime" maxlength=5><img src="<?php echo EVENTSCALENDARIMAGESURL; ?>/time.png"><?php /*<img src="<?php echo EVENTSCALENDARIMAGESURL."/time.png";?>" id="EC_end_clockpick" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>*/?></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_accessLevel"><?php _e('Visibility Level','events-calendar'); ?></label></th>
          <td>
            <select name="EC_accessLevel" id="EC_accessLevel">
              <option value="public"><?php _e('Public','events-calendar'); ?></option>
              <option value="level_10"><?php _e('Administrator','events-calendar'); ?></option>
              <option value="level_7"><?php _e('Editor','events-calendar'); ?></option>
              <option value="level_2"><?php _e('Author','events-calendar'); ?></option>
              <option value="level_1"><?php _e('Contributor','events-calendar'); ?></option>
              <option value="level_0"><?php _e('Subscriber','events-calendar'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="doPost"><?php _e('Create Post for Event','events-calendar'); ?></label></th>
          <td><input type="checkbox" name="EC_doPost" id="EC_doPost"/></td>
        </tr>
      </table>
      <table id="EC_management-post-status" summary="Event Post Status" class="ec-edit-form" width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th scope="row"><label for="statusPost"><?php _e('Which Post Status ?','events-calendar'); ?></label></th>
          <td>
            <select name="EC_statusPost" id="EC_statusPost">
              <option value="draft" selected="selected" ><?php _e('Draft','events-calendar'); ?></option>
              <option value="publish" ><?php _e('Publish','events-calendar'); ?></option>
            </select>
          </td>
        </tr>
      </table>
      <input type="hidden" name="EC_addEventFormSubmitted" value="1">
      <p class="submit">
        <input type="submit" name="submit" value="<?php _e('Add Event','events-calendar'); ?> &raquo;">
      </p>
    </form>
    </div>
    <script>
      function ec_parse_float(valtime)
			{
        var hr = valtime.substr(0,2);
        var mm = valtime.substr(3,2);
        return parseFloat(hr + '.' + mm);
      }
      function valid_addEventForm()
			{
        if (document.forms.EC_addEventForm.EC_title.value == '')
				{
          alertmsgbox("<?php _e('Event Title can not be blank!','events-calendar'); ?>");
          document.forms.EC_addEventForm.EC_title.focus();
          return false;
        }
			
        var stt = ec_parse_float(document.forms.EC_addEventForm.EC_startTime.value);
				var edt = ec_parse_float(document.forms.EC_addEventForm.EC_endTime.value);
				var startDt =            document.forms.EC_addEventForm.EC_startDate.value;
				var endDt =              document.forms.EC_addEventForm.EC_endDate.value;

		 		if (endDt == null || endDt == undefined || endDt == '')
					endDt = startDt;

        if (endDt < startDt)
				{
					alertmsgbox("<?php _e('The end date is earlier than the start date.', 'events-calendar');?>");
					document.forms.EC_addEventForm.EC_endDate.focus();
					return false;
				}

        if (startDt == endDt && edt < stt)
				{
					alertmsgbox("<?php _e('The end time is earlier than the start time ;-)','events-calendar'); ?>");
					document.forms.EC_addEventForm.EC_endTime.focus();
					return false;
        }
      }
      function alertmsgbox(msg)
			{
        jQuery("#EC_alertmsg p").text(msg);
        jQuery("#EC_alertmsg").show();
        jQuery("#EC_alertmsg").css('top', '885px');
      }
			function ShowHidePostStatus()
			{
				if (jQuery("#EC_doPost").is(":checked")) {
					jQuery("#EC_management-post-status").show("slow");
				} else {
					jQuery("#EC_management-post-status").hide("slow");
				}
			}
      jQuery("form[name='EC_addEventForm']").ready(ShowHidePostStatus);
      jQuery("#EC_doPost").click(ShowHidePostStatus);
      jQuery(document).ready(function()
			{
				jQuery("#EC_close_message_alert").click(function() {
						jQuery("#EC_alertmsg").fadeOut("slow");
				});
				jQuery("#EC_alertmsg").hide();
      });
    </script>
<?php
	}

	/**
	 * Outputs the Edit Event form.
	 *
	 * Provides the HTML and Javascript necessary for the user to add and validate a new event.
	 *
	 * @param int $id  the event id.
	 */
	function editEventForm($id)
	{
		if( !is_numeric( $id ) )
			die( 'You should not be here.' );
		$event = $this->db->getEvent($id);
		$event = $event[0];
		$linkout = !is_null($event->eventLinkout) ? stripslashes($event->eventLinkout) : $this->deflinkout;
?>
    <h2><?php _e('Edit Event','events-calendar'); ?></h2>
    <form name="EC_editEventForm" method="post" action="?page=events-calendar" onSubmit="return valid_editEventForm();" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>
      <p class="submit">
        <input type="submit" name="submit" value="<?php _e('Update Event','events-calendar'); ?> &raquo;">
      </p>
    <div id="EC_alertmsg" class="alertmsg">
      <img id="EC_close_message_alert" style="float:right;cursor:pointer" src="<?php echo EVENTSCALENDARIMAGESURL."/cross.png";?>">
      <img id="ec-alert-img" src="<?php echo EVENTSCALENDARIMAGESURL."/alert.png";?>" style="vertical-align:middle;"> <strong><?php _e('Warning','events-calendar'); ?></strong>
      <p>message</p>
    </div>
      <table id="EC_management-edit-form" summary="Event Edit Form" class="ec-edit-form">
        <tr>
          <th scope="row"><label for="EC_title"><?php _e('Title','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_title" id="EC_title" value="<?php echo htmlentities(stripslashes($event->eventTitle),ENT_QUOTES);?>" required></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_location"><?php _e('Location','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_location" id="EC_location" value="<?php echo htmlentities(stripslashes($event->eventLocation),ENT_QUOTES);?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_linkout"><?php _e('Link out','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" type="text" name="EC_linkout" id="EC_linkout" style="width:300px;" value="<?php echo $linkout;?>"/></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_description"><?php _e('Description','events-calendar'); ?></label></th>
          <td><textarea class="ec-edit-form-textarea" name="EC_description" id="EC_description"><?php echo stripslashes($event->eventDescription);?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_startDate"><?php _e('Start Date (YYYY-MM-DD, if blank will be today)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-date" autocomplete="OFF" type="text" name="EC_startDate" id="EC_startDate" value="<?php echo $event->eventStartDate;?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_startTime"><?php _e('Start Time (HH:MM, can be blank)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-time" autocomplete="OFF" type="text" name="EC_startTime" id="EC_startTime" value="<?php echo $event->eventStartTime;?>"><?php /*<img src="<?php echo EVENTSCALENDARIMAGESURL."/time.png";?>" width="20" height="20" id="EC_start_clockpick" style="vertical-align:middle;" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>*/?></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_endDate"><?php _e('End Date (YYYY-MM-DD, if blank will be same as start date)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-date" autocomplete="OFF" type="text" name="EC_endDate" id="EC_endDate" value="<?php echo $event->eventEndDate;?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_endTime"><?php _e('End Time (HH:MM, can be blank)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-time" autocomplete="OFF" type="text" name="EC_endTime" id="EC_endTime" value="<?php echo $event->eventEndTime;?>"><?php /*<img src="<?php echo EVENTSCALENDARIMAGESURL."/time.png";?>" width="20" height="20" id="EC_end_clockpick" style="vertical-align:middle;" onClick='jQuery("#EC_alertmsg").fadeOut("slow");'>*/?></td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_accessLevel"><?php _e('Visibility Level','events-calendar'); ?></label></th>
          <td>
            <select name="EC_accessLevel" id="EC_accessLevel">
              <option value="public" <?php if($event->accessLevel == 'public') echo 'selected="selected"';?>><?php _e('Public','events-calendar'); ?></option>
              <option value="level_10" <?php if($event->accessLevel == 'level_10') echo 'selected="selected"';?>><?php _e('Administrator','events-calendar'); ?></option>
              <option value="level_7" <?php if($event->accessLevel == 'level_7') echo 'selected="selected"';?>><?php _e('Editor','events-calendar'); ?></option>
              <option value="level_2" <?php if($event->accessLevel == 'level_2') echo 'selected="selected"';?>><?php _e('Author','events-calendar'); ?></option>
              <option value="level_1" <?php if($event->accessLevel == 'level_1') echo 'selected="selected"';?>><?php _e('Contributor','events-calendar'); ?></option>
              <option value="level_0" <?php if($event->accessLevel == 'level_0') echo 'selected="selected"';?>><?php _e('Subscriber','events-calendar'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="EC_postID"><?php _e('Post ID of Associated post (0 for no associated post)','events-calendar'); ?></label></th>
          <td><input class="ec-edit-form-text" autocomplete="OFF" type="number" min=0 name="EC_postID" id="EC_postID" value="<?php echo $event->postID;?>" onChange="postIDtst()"/></td>
        </tr>
      </table>
      <input type="hidden" name="EC_editEventFormSubmitted" value="1">
      <input type="hidden" name="EC_id" value="<?php echo $id;?>">
      <p class="submit">
        <input type="submit" name="submit" value="<?php _e('Update Event','events-calendar'); ?> &raquo;">
      </p>
    </form>
    <script>
      function onfocus_addEventForm()
			{
        jQuery("#EC_alertmsg").fadeOut("slow");
      }
      function ec_parse_float(valtime)
			{
        var hr = valtime.substr(0,2);
        var mm = valtime.substr(3,2);
        return parseFloat(hr+"."+mm);
      }
      function valid_editEventForm()
			{
        if (document.forms.EC_editEventForm.EC_title.value=="")
				{
          alertmsgbox("<?php _e('Event Title can not be blank!','events-calendar'); ?>");
          document.forms.EC_editEventForm.EC_title.focus();
          return false;
        }
        var stt = ec_parse_float(document.forms.EC_editEventForm.EC_startTime.value);
        var edt = ec_parse_float(document.forms.EC_editEventForm.EC_endTime.value);
				var startDt = document.forms.EC_addEventForm.EC_startDate.value;
				var endDt = document.forms.EC_addEventForm.EC_endDate.value;

				if (endDt == null || endDT == undefined || endDt == '')
					endDt = startDt;

        if (endDt < startDt)
				{
					alertmsgbox("<?php _e('The end date is earlier than the start date.', 'events-calendar');?>");
					document.forms.EC_addEventForm.EC_endDate.focus();
					return false;
				}
        if ((startDt == endDt) && (edt < stt))
				{
          alertmsgbox("<?php _e('The end time is earlier than the start time ;-)','events-calendar'); ?>");
          document.forms.EC_editEventForm.EC_endTime.focus();
          return false;
        }
				return postIDtst();
      }

      function postIDtst()
			{
        var pid = document.forms.EC_editEventForm.EC_postID.value;
        if (pid == '')
					return true;
        var m = parseFloat(pid);
        if (isNaN(m))
				{
          alertmsgbox("<?php _e('Post ID must be a number!','events-calendar'); ?>");
          return false;
        } else {
          m = Number(document.forms.EC_editEventForm.EC_postID.value);
          if (isNaN(m))
					{
            alertmsgbox("<?php _e('Post ID must be a number!','events-calendar'); ?>");
            return false;
          }
        }
        return true;
      }
      function alertmsgbox(msg)
			{
        jQuery("#EC_alertmsg p").text(msg);
        jQuery("#EC_alertmsg").show();
				jQuery("#EC_alertmsg").css('top', '300px');
      }

      jQuery(document).ready(function()
			{
				jQuery("#EC_close_message_alert").click(function() {
						jQuery("#EC_alertmsg").fadeOut("slow");
				});
				jQuery("#EC_alertmsg").hide();
				jQuery("a[href='#addEventform']").hide();
      })(jQuery);
    </script>
<?php
	}

	/**
	 * Outputs the widget control in admin panel and updates options if the form is submitted.
	 */
	function widgetControl()
	{
		$options = get_option('widgetEventsCalendar');
		if (!is_array($options))
		{
			$options = array(
				'title' => __('Events Calendar','events-calendar'),
				'type' => 'calendar',
				'listCount' => 5,
			);
		}
		
		if (isset($_POST['eventscalendar']))
		{
			if (isset($_POST['eventscalendar']['submit']) )
				unset($_POST['eventscalendar']['submit']);

			foreach ($_POST['eventscalendar'] as $key => $option)
				$options[$key] = strip_tags(stripslashes($option));

			update_option('widgetEventsCalendar', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		echo '<p style="text-align:center;"><label for="eventscalendar-title">' . __("Title","events-calendar") . ': ';
		echo '<input style="width: 200px;" id="eventscalendar-title" name="eventscalendar[title]" type="text" value="' . $title . '"></label></p>';
    ?>
      <p style="text-align:center;">
        <label for="eventscalendar-type">
          <?php printf(__("Calendar Type","events-calendar")) ?>:
          <select name="eventscalendar[type]" id="eventscalendar-type">
            <option value="calendar"><?php printf(__("Calendar","events-calendar")) ?></option>
            <option value="list" <?php if ( isset($options['type']) && 'list' == $options['type'] ) echo 'selected="selected"'; ?>><?php printf(__("List","events-calendar")) ?></option>
          </select>
        </label>
      </p>
      <div id="eventscalendar-EventListOptions" style="<?php if ( !isset($options['type']) || 'list' != $options['type'] ) echo 'display: none;'; ?>">
        <p>
          <span style="font-weight: bold"><?php printf(__("List options","events-calendar")) ?></span>
        </p>
        <p>
          <label for="eventscalendar-listCount">
            <?php printf(_e("Number of events","events-calendar")) ?>:
            <input style="width: 30px;" type="text" id="eventscalendar-listCount" name="eventscalendar[listCount]" value="<?php echo ( isset($options['listCount']) && !empty($options['listCount']) ) ? $options['listCount'] : '5'; ?>">
          </label>
        </p>
      </div>

    <?php
		echo '<input type="hidden" id="eventscalendar-submit" name="eventscalendar[submit]" value="1">';
    ?>
      <script>
        jQuery.noConflict();
        jQuery("select#eventscalendar-type").change(function(){
          if ("list" == this.value)
					{
            jQuery("#eventscalendar-EventListOptions").show();
          } else {
            jQuery("#eventscalendar-EventListOptions").hide();
          }
        });
      </script>
    <?php
	}

	/**
	 * Provides the admin option panel.
	 */
	function calendarOptions()
	{
		$options = get_option('optionsEventsCalendar');
		if(!is_array($options))
		{
			$options = array(
				'dateFormatWidget' => 'm-d',
				'timeFormatWidget' => 'h:i a',
				'dateFormatLarge' => 'n/j/Y',
				'timeFormatLarge' => 'h:i a',
				'adaptedCSS' => '',
				'disableTooltips' => '',
				'dayHasEventCSS' => 'color:red',
				'timeStep' => '30',
				'daynamelength' => '3',
				'daynamelengthLarge' => '3',
				'jqueryextremstatus' => 'false',
			);
		}
		if (isset($_POST['optionsEventsCalendarSubmitted']) && $_POST['optionsEventsCalendarSubmitted'])
		{
			$options['dateFormatWidget'] = isset($_POST['dateFormatWidget']) && !empty($_POST['dateFormatWidget']) ? $_POST['dateFormatWidget'] : 'm-d';
			$options['timeFormatWidget'] = isset($_POST['timeFormatWidget']) && !empty($_POST['timeFormatWidget']) ? $_POST['timeFormatWidget'] : 'g:i a';
			$options['dateFormatLarge'] = isset($_POST['dateFormatLarge']) && !empty($_POST['dateFormatLarge']) ? $_POST['dateFormatLarge'] : 'n/j/Y';
			$options['timeFormatLarge'] = isset($_POST['timeFormatLarge']) && !empty($_POST['timeFormatLarge']) ? $_POST['timeFormatLarge'] : 'g:i a';
			$options['timeStep'] = isset($_POST['timeStep']) && !empty($_POST['timeStep']) ? $_POST['timeStep'] : '30';
			$options['adaptedCSS'] = isset($_POST['adaptedCSS']) ? $_POST['adaptedCSS'] : '';
			$options['disableTooltips'] = isset($_POST['disableTooltips']) ? $_POST['disableTooltips'] : '';
			$options['dayHasEventCSS'] = isset($_POST['dayHasEventCSS']) && !empty($_POST['dayHasEventCSS']) ? $_POST['dayHasEventCSS'] : 'color:red;';
			$options['daynamelength'] = isset($_POST['daynamelength']) && !empty($_POST['daynamelength']) ? $_POST['daynamelength'] : '3';
			$options['daynamelengthLarge'] = isset($_POST['daynamelengthLarge']) && !empty($_POST['daynamelengthLarge']) ? $_POST['daynamelengthLarge'] : '3';
			$options['jqueryextremstatus'] = isset($_POST['jqxstatus']) ? $_POST['jqxstatus'] : 'false';
			$options['accessLevel'] = $_POST['EC_accessLevel'];

			update_option('optionsEventsCalendar', $options);
		}
?>
    <div class="wrap"><h2 style="border:none;margin-top:12px;"><?php _e('Events Calendar Options','events-calendar'); ?></h2></div>
    <form name="optionsEventsCalendar" method="post" action="?page=events-calendar-options" onLoad="">
      <p class="submit">
        <input type="submit" name="submit" value="<?php _e('Update Options','events-calendar'); ?> &raquo;">
      </p>
      <table summary="Edit Access Level Form" class="form-table" width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="EC_accessLevel"><?php _e('Access Level','events-calendar'); ?></label></th>
          <td width="67%">
            <select name="EC_accessLevel" id="accessLevel">
              <option value="level_10" <?php if($options['accessLevel'] == 'level_10') echo 'selected="selected"';?>><?php _e('Administrator','events-calendar'); ?></option>
              <option value="level_7" <?php if($options['accessLevel'] == 'level_7') echo 'selected="selected"';?>><?php _e('Editor','events-calendar'); ?></option>
              <option value="level_2" <?php if($options['accessLevel'] == 'level_2') echo 'selected="selected"';?>><?php _e('Author','events-calendar'); ?></option>
              <option value="level_1" <?php if($options['accessLevel'] == 'level_1') echo 'selected="selected"';?>><?php _e('Contributor','events-calendar'); ?></option>
              <option value="level_0" <?php if($options['accessLevel'] == 'level_0') echo 'selected="selected"';?>><?php _e('Subscriber','events-calendar'); ?></option>
            </select>
          </td>
        </tr>
        </table>
        <table summary="Edit Form2" class="form-table" width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th style="text-align:right;border:none;"><label><?php _e('Date/Time Formatting(see','events-calendar'); ?> <a href="http://us2.php.net/date" target="_blank">PHP Date</a>) :</label></th>
          <td style="border:none;"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;border:none;"><label for="dateFormatWidget"><?php _e('Widget Calendar Dates','events-calendar'); ?></label></th>
          <td width="67%" style="border:none;"><input type="text" name="dateFormatWidget" id="dateFormatWidget" value="<?php echo $options['dateFormatWidget'];?>"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;border:none;"><label for="timeFormatWidget"><?php _e('Widget Calendar Times','events-calendar'); ?></label></th>
          <td width="67%" style="border:none;"><input type="text" name="timeFormatWidget" id="timeFormatWidget" value="<?php echo $options['timeFormatWidget'];?>"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;border:none;"><label for="dateFormatLarge"><?php _e('Large Calendar Dates','events-calendar'); ?></label></th>
          <td width="67%" style="border:none;"><input type="text" name="dateFormatLarge" id="dateFormatLarge" value="<?php echo $options['dateFormatLarge'];?>"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;border:none;"><label for="timeFormatLarge"><?php _e('Large Calendar Times','events-calendar'); ?></label></th>
          <td width="67%" style="border:none;"><input type="text" name="timeFormatLarge" id="timeFormatLarge" value="<?php echo $options['timeFormatLarge'];?>"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="timeStep"><?php _e('Step Increment for Time Selector (in minutes)','events-calendar'); ?></label></th>
          <td width="67%"><input type="text" name="timeStep" id="timeStep" value="<?php echo $options['timeStep'];?>"></td>
        </tr>
		<tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="disableTooltips"><?php _e('Disable Tooltips (Checked = Yes)','events-calendar'); ?></label></th>
          <td width="67%"><input type="checkbox" <?php echo ($options['disableTooltips']==true) ? "checked " : "";?>name="disableTooltips" id="EC_disableTooltips" value="yes"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="adaptedCSS"><?php _e('I have adapted the Events-Calendar stylesheet (checked = yes)','events-calendar'); ?></label></th>
          <td width="67%"><input type="checkbox" <?php echo ($options['adaptedCSS']==true) ? "checked " : "";?>name="adaptedCSS" id="EC_adaptedCSS" value="on"></td>
        </tr>
        </table>
        <table id="switchCSSoptions" summary="Edit Form3" class="form-table" width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;" id="EC_dayHasEventCSS_label"><label for="dayHasEventCSS"><?php _e('CSS for Day With Events','events-calendar'); ?></label></th>
          <td width="67%"><input type="text" name="dayHasEventCSS" id="EC_dayHasEventCSS" value="<?php echo $options['dayHasEventCSS'];?>"></td>
        </tr>
        </table>
        <table summary="Edit Form4" class="form-table" width="100%" cellspacing="2" cellpadding="5">
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="daynamelength"><?php _e('Length of day names in Widget Calendar','events-calendar'); ?></label></th>
          <td width="67%"><input type="text" name="daynamelength" id="EC_daynamelength" value="<?php echo $options['daynamelength'];?>"></td>
        </tr>
				<tr>
        	<th width="33%" scope="row" valign="top" style="text-align:right;"><label for="daynamelengthLarge"><?php _e('Length of day names in Large Calendar','events-calendar'); ?></label></th>
          <td width="67%"><input type="text" name="daynamelengthLarge" id="EC_daynamelengthLarge" value="<?php echo $options['daynamelengthLarge'];?>"></td>
        </tr>
        <tr>
          <th width="33%" scope="row" valign="top" style="text-align:right;"><label for="jqxstatus"><?php _e('jQuery Extrem Protection (checked = yes)','events-calendar'); ?></label></th>
          <td width="67%"><input type="checkbox" <?php echo ($options['jqueryextremstatus']=="true") ? " checked" : "";?> name="jqxstatus" id="EC_jqxstatus" value="true"> <?php _e('(Check if you don\'t see the Tooltips)','events-calendar'); ?></td>
        </tr>
      </table>
      <input type="hidden" name="optionsEventsCalendarSubmitted" value="1">
      <p class="submit">
        <input type="submit" name="submit" value="<?php _e('Update Options','events-calendar'); ?> &raquo;">
      </p>
    </form>
    <script>
      jQuery.noConflict();

			function switchCSSoptions()
			{
				if (jQuery("#EC_adaptedCSS").is(":checked")) {
					jQuery("#switchCSSoptions").hide("slow");
				} else {
					jQuery("#switchCSSoptions").show("slow");
				}
			}

      jQuery("form[name='optionsEventsCalendar']").ready(switchCSSoptions);
      jQuery("#EC_adaptedCSS").click(switchCSSoptions);
      jQuery(document).ready(function()
			{
				jQuery("a[href='#addEventform']").hide();
      });
    </script>
<?php
  }
}
}
?>