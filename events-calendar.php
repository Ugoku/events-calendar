<?php
/*
Plugin Name: WP Events Calendar
Plugin URI: http://www.ugoku.nl/
Description: There are options under the widget options to specify the view of the calendar in the sidebar. The widget can be a list for upcoming events or a calendar. If you do not have a widget ready theme then you can place `&lt;?php SidebarEventsCalendar();?&gt;`, or `&lt;?php SidebarEventsList();?&gt;` for an event list, in the sidebar.php file of your theme. If you want to display a large calendar in a post or a page, simply place `[events-calendar-large]` in the html of the post or page. Make sure to leave off the quotes.
Version: 7.0
Author: Ugoku, based on code by Luke Howell
Author URI: http://www.ugoku.nl/
Licence: GPLv3 {@link http://www.gnu.org/licenses/gpl}
*/

/** Set timezone **/
if (function_exists ('date_default_timezone_set'))
	date_default_timezone_set(get_option('timezone_string'));

/** Events-Calendar version */
define('EVENTSCALENDARVERS', 'Version: 7.0');

/** using native directory separator for paths */
if (!defined('DS'))
	define ('DS', DIRECTORY_SEPARATOR);

// Paths
define('EVENTSCALENDARPATH', ABSPATH.'wp-content/plugins/events-calendar');
define('EVENTSCALENDARCLASSPATH', EVENTSCALENDARPATH);
define('ABSWPINCLUDE', ABSPATH.WPINC);

// URLS
define('EVENTSCALENDARURL', get_option('siteurl').'/wp-content/plugins/events-calendar');
define('EVENTSCALENDARJSURL', EVENTSCALENDARURL.'/js');
define('EVENTSCALENDARCSSURL', EVENTSCALENDARURL.'/css');
define('EVENTSCALENDARIMAGESURL', EVENTSCALENDARURL.'/images');


require_once(EVENTSCALENDARCLASSPATH.'/ec_day.class.php');
require_once(EVENTSCALENDARCLASSPATH.'/ec_calendar.class.php');
require_once(EVENTSCALENDARCLASSPATH.'/ec_db.class.php');
require_once(EVENTSCALENDARCLASSPATH.'/ec_widget.class.php');
require_once(EVENTSCALENDARCLASSPATH.'/ec_management.class.php');
require_once(ABSPATH.'wp-includes/pluggable.php');

/** Init Localisation */
load_default_textdomain();
load_plugin_textdomain('events-calendar',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/lang');

/** DatePicker localisation */
$locale = get_locale();
$loc_lang = explode("_",$locale);
$loc_lang = $loc_lang[0];
if (!in_array ($loc_lang, array('ar','bg','ca','cs','da','de','es','fi','fr','he','hu','hy','id','is','it','ja','ko','lt','lv','nl','no','pl','ro','ru','sk','sv','th','tr','uk')))
{
	$loc_lang='en';
}


if (isset ($_GET['EC_view']) && $_GET['EC_view'] == 'day')
{
	EC_send_headers();
	$EC_date = date('Y-m-d', mktime(0, 0, 0, $_GET['EC_month'], $_GET['EC_day'], $_GET['EC_year']));
	$day = new EC_Day();
	$day->display($EC_date);
	exit;
}

// Called from the large calendar through AJAX.
// We need to send a header to make sure we respect the blog charset.
if (isset ($_GET['EC_action']) && $_GET['EC_action'] == 'switchMonth')
{
	EC_send_headers();
	$calendar = new EC_Calendar();
	$calendar->displayWidget($_GET['EC_year'], $_GET['EC_month']);
	exit;
}

// Called from the large calendar through AJAX.
// We need to send a header to make sure we respect the blog charset.
if (isset ($_GET['EC_action']) && $_GET['EC_action'] == 'switchMonthLarge')
{
	EC_send_headers();
	$calendar = new EC_Calendar();
	$calendar->displayLarge($_GET['EC_year'], $_GET['EC_month']);
	exit;
}

if (isset ($_GET['EC_action']) && $_GET['EC_action'] == 'ajaxDelete')
{
	$db = new EC_DB();
	$db->deleteEvent($_GET['EC_id']);
	exit;
}

// Sends headers needed when AJAX is used.
function EC_send_headers()
{
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header("Pragma: no-cache");                          // HTTP/1.0
	header("Content-Type: text/html; charset=".get_option('blog_charset'));
}

/**
 * Initializes the Events Calendar plugin.
 *
 * The function first check to see if we are in the admin panel.
 * If we're not, it enqueues the jQuery plugins needed by WPEC:
 * bgiframe, dimensions, tooltip and thinkbox.
 * Then it registers the widget and the widget control with WordPress.
 * @uses EC_Widget
 * @uses EC_Management
 */

/* updated INIT function for EC6.6.1 patch by Byron Rode */
function EventsCalendarINIT()
{
	$options = get_option('optionsEventsCalendar');
	$inadmin = is_admin();

	if (!$inadmin)
	{
		wp_enqueue_script('jquerybgiframe', '/wp-content/plugins/events-calendar/js/jquery.bgiframe.js', array('jquery'), '2.1');
		// wp_enqueue_script('jquerydimensions', '/wp-content/plugins/events-calendar/js/jquery.dimensions.js', array('jquery'), '1.0b2');
		if($options['disableTooltips'] !== 'yes')
		{
			wp_enqueue_script('jquerytooltip', '/wp-content/plugins/events-calendar/js/jquery.tooltip.min.js', array('jquery'), '1.3');
		}
		wp_enqueue_script('thickbox');
	}

	// Always register both the widget and widget control objects
	// in case there are dependencies between the two.
	$widget = new EC_Widget();
	$management = new EC_Management();
	register_sidebar_widget(__('Events Calendar','events-calendar'), array(&$widget, 'display'));
	register_widget_control(__('Events Calendar','events-calendar'), array(&$management, 'widgetControl'));
}

/**
 * Initializes the Events Calendar admin panel.
 * The function creates a new menu and enqueues a few jquery plugins:
 * bgiframe, dimensions, tooltip, ui.core, ui.datepicker and its language file,
 * clockpicker,
 *
 * @uses EC_Management
 */
function EventsCalendarManagementINIT()
{
	$options = get_option ('optionsEventsCalendar');
	$EC_userLevel = isset ($options['accessLevel']) && !empty ($options['accessLevel']) ? $options['accessLevel'] : 'level_10';
	$management = new EC_Management();
	add_menu_page(__('Events Calendar','events-calendar'), __('Events Calendar','events-calendar'), $EC_userLevel, 'events-calendar', array(&$management, 'display'));
	if(isset($_GET['page']) && strstr($_GET['page'], 'events-calendar'))
	{
		global $loc_lang;
		//wp_enqueue_script('jquerybgiframe', '/wp-content/plugins/events-calendar/js/jquery.bgiframe.js', array('jquery'), '2.1');
		//wp_enqueue_script('jquerydimensions', '/wp-content/plugins/events-calendar/js/jquery.dimensions.js', array('jquery'), '1.0b2');
		wp_enqueue_script('jquerytooltip', '/wp-content/plugins/events-calendar/js/jquery.tooltip.min.js', array('jquery'), '1.3');
		wp_enqueue_script('jqueryuicore', '/wp-content/plugins/events-calendar/js/ui.core.min.js', array('jquery'), '1.5.2');
		wp_enqueue_script('jqueryuidatepicker', '/wp-content/plugins/events-calendar/js/ui.datepicker.js', array('jquery'), '1.5.2');

		if ($loc_lang !== 'en')
			wp_enqueue_script('jqueryuidatepickerlang', '/wp-content/plugins/events-calendar/js/i18n/ui.datepicker-'.$loc_lang.'.js', array('jquery'), '1.5.2');

		wp_enqueue_script('jqueryclockpicker', '/wp-content/plugins/events-calendar/js/jquery.clockpick.min.js', array('jquery'), '1.2.6');

		add_submenu_page('events-calendar', __('Events Calendar','events-calendar'), __('Calendar','events-calendar'), $EC_userLevel, 'events-calendar', '');

		add_submenu_page('events-calendar', __('Events Calendar','events-calendar'), __('Add Event','events-calendar'), $EC_userLevel, '#addEventform', '');
		add_submenu_page('events-calendar', __('Events Calendar Options','events-calendar'), __('Options','events-calendar'), $EC_userLevel, 'events-calendar-options', array(&$management, 'calendarOptions'));
	}
}

/**
 * Loads the stylesheets and the jQuery library.
 * The function generates a call to jQuery,noConflict() and passes it the jQuery
 * Extreme Flag that can be set/unset in the admin panel.
 * The jQuery object is stored in ecd.jq which will then be used by the plugin.
 */
function EventsCalendarHeaderScript()
{
?>
<!-- Start of script generated by Events Calendar -->
<link type="text/css" rel="stylesheet" href="<?php bloginfo('wpurl'); ?>/wp-includes/js/thickbox/thickbox.css">
<link type="text/css" rel="stylesheet" href="<?php echo EVENTSCALENDARCSSURL; ?>/events-calendar.css">
<?php
	require_once(ABSPATH . 'wp-admin/includes/admin.php');
	// jQuery DOM extreme protection management
	$options = get_option('optionsEventsCalendar');
	echo ' <script>',"\n\t";
	echo 'var ecd = {};',"\n\t";
	echo 'ecd.jq = jQuery.noConflict('.$options['jqueryextremstatus'].');',"\n\t";
	echo ' </script>',"\n";
	echo "<!-- End of script generated by Events Calendar -->\n";
}

/**
 * Loads the needed stylesheets for the admin panel.
 */
function EventsCalendarAdminHeaderScript()
{
	if (isset($_GET['page']) && $_GET['page'] == 'events-calendar')
	{
?>
<link type="text/css" rel="stylesheet" href="<?php echo EVENTSCALENDARCSSURL; ?>/events-calendar-management.css">
<link type="text/css" rel="stylesheet" href="<?php echo EVENTSCALENDARCSSURL; ?>/ui.datepicker.css">
<link type="text/css" rel="stylesheet" href="<?php echo EVENTSCALENDARCSSURL; ?>/clockpick.css">
<?php
	}
}

/**
 * Installs or upgrade the plugin on activation.
 * This is why it is important to de-activate the plugin before
 * upgrading it.
 * @uses EC_DB
 */
function EventsCalendarActivated()
{
	$db = new EC_DB();
	$db->createTable();
	$db->initOptions();
}

/**
 * Either returns needle or the data before needle.
 *
 * This is used by the filterEventsCalendarLarge() function to get
 * the content of a page before and after the short tag [[EventsCalendarLarge]]
 *
 * @param string $haystack      page or post swhere the shrt tag lives
 * @param string $needle        the short tag
 * @param bool   $before_needle do we want the data before the short tag?
 * @return string
 */
function ec_strstr($haystack, $needle, $before_needle=FALSE)
{
	if (FALSE === ($pos = strpos($haystack, $needle)))
		return FALSE;

	if ($before_needle)
		return substr($haystack, 0, $pos);
	else
		return substr($haystack, $pos + strlen($needle));
}

/**
 * Displays the large calendar in place of the [[EventsCalendarLarge]] short tag.
 *
 * @param string $content 		the content of the page
 * @return string             the content after the tag
 * @uses ec_strstr()
 * @uses EC_Calendar
 */


/* New function with updated function argument at the end of the function. 
 * @bool(true) - echo's out content1
 * @bool(false) - returns content, works better for use in WP Shortcode
 */
function filterEventsCalendarLarge()
{
	$calendar = new EC_Calendar();
	return $calendar->displayLarge(date('Y'), date('m'), "", array(), 7, false);
}

/**
 * Will display the small calendar in sidebar.
 *
 * This can be used by themes that are not widget ready.
 */
function SidebarEventsCalendar()
{
	$calendar = new EC_Calendar();
	$calendar->displayWidget(date('Y'), date('m'));
}

/**
 * Will display an events list in sidebar.
 *
 * This can be used by themes that are not widget ready.
 *
 * @param int $num 		number of events to display. defaults to 5.
 */
function SidebarEventsList($num = 5)
{
	$calendar = new EC_Calendar();
	$calendar->displayEventList($num);
}

add_action('activate_events-calendar/events-calendar.php', 'EventsCalendarActivated');
add_action('init', 'EventsCalendarINIT');
add_action('admin_menu', 'EventsCalendarManagementINIT');
add_action('wp_head', 'EventsCalendarHeaderScript');
add_action('admin_head', 'EventsCalendarAdminHeaderScript');
add_shortcode('events-calendar-large', 'filterEventsCalendarLarge');
?>