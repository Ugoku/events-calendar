=== Events Calendar ===

Contributors: Sander de Jong, Luke Howell
Version: 7.0
Tags: event, calendar, date, time, widget, admin, sidebar, plugin, javascript, thickbox, jquery, tooltip, ajax
Requires at least: 3.6.0
Tested up to: 3.6.1
Stable tag: 7.0

Events-Calendar is a versatile replacement for the original WordPress calendar adding many useful functions to keep track of your events.

== Description ==

Events-Calendar is a versatile replacement for the original calendar included with WordPress adding many useful functions to keep track of your events. The plugin has an easy to use admin section that displays a big readable calendar and lets you add and delete events.

The plugin is widget ready so you can easily add a small calendar to the main sidebar with the ability to roll over the highlighted event day to see a brief description of the event or click the day to get a full description of the event without ever leaving your current page.

If you are not using a widget ready theme, you can still have the calendar on your sidebar.  Simply place `<?php sidebarEventsCalendar();?>` (or `<?php sidebarEventsList($number_of_items);?>` if you want a list) in the sidebar file. The widget can also show a specified number of events as a list.  You will find these options under the widget option.

The ability to add a large public calendar is available by posting a page and adding `[events-calendar-large]` to the page content to create a stand alone calendar page. Also, when entering an event from the admin section, you can check the box saying "Create Post for Event", which will cause a post to be created with the event information.

== Installation ==

1. Upload `events-calendar` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the Plugins menu in the Dashboard.
3. Set options under Events Calendar/Options on the admin menu.
	
== Screenshots ==

1. Events Calendar Admin
2. Events Calendar Options
3. Events Calendar Widget Options
4. Events Calendar as Widget Calendar
5. Events Calendar as Widget List
6. Events Calendar as Large Calendar

== Upgrade Notice ==
= 7.0 =
* First release by new contributor. Mostly code cleanup, no new features.

= 6.7.13 =
* This update fixes the issue with slashes in events.  This will work on new events, and will be corrected when editing events.  Backup before upgrading.

= 6.7.12a =
* This update fixes an XSS injection attack to the Wordpress plugin admin page that allowed for execution of arbitrary HTML code.  When updating please backup your CSS file if you have made customizations to the stylesheet.

== Changelog ==
= 6.7.13 =
* This update fixes the issue with slashes in events.  This will work on new events, and will be corrected when editing events.  Backup before upgrading.

= 6.7.12 =
* This update fixes an XSS injection attack to the Wordpress plugin admin page that allowed for execution of arbitrary HTML code.  When updating please backup your CSS file if you have made customizations to the stylesheet.

= 6.7.11 =
* Removing sponsor message

= 6.7.10 =
* Fixed SQL injection vulnerability pointed out by @zap1989

= 6.7.9 =
* Changed the way the sponsor message is shown and hidden to prevent have hidden links that were hurting SEO.

= 6.7.8 =
* Checking for existance of timezone function
* Add Japanese language file from blog.bng.net

= 6.7.7 =
* Timezone is now reading from wordpress timezone for choosing current day.  I am silly.

= 6.7.6 =
* Added option to hide Sponsor messages
* Fix added for conflict with ddsmoothmenu.
* Removed call to dimensions jquery plugin.

= 6.7.5 =
* Removed calendar and time select buttons until errors are resolved.
* Added sponsorship message.

= 6.7.3 =
* Corrected problem where quotes not escaped properly in admin section with title.  Thanks Pat

= 6.7.2 =
* Events and tooltip issues in large calendar.

= 6.7.1 =
* Fixed disappearing tooltips.

= 6.7 =
* Fixed the hover error to show info when hover over date.

== Frequently Asked Questions ==

= I use a theme with a dark background.  My events don't show well in the large calendar view. =

In the css folder there is a file called events-calendar.css. This file has the css for the calendar. It is commented as Large Calendar.