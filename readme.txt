=== WP VisitorFlow ===
Contributors: friese
Donate link: https://www.datacodedesign.de/index.php/wp-visitorflow-donate/
Tags: statistics, analytics, web analytics, stats, visit, visitors, page, page view, page hit, visitor flow, chart, pagerank, bounce, bounce rate, exit page
Requires at least: 3.5
Tested up to: 4.7.4
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Detailed web analytics and visualization of your website's visitor flow.

== Description ==

WP VisitorFlow provides you with detailed information about your website's visitors. With WP VisitorFlow you can see at a glance how visitors interact with your website: all routes your visitors take are summarized in a comprehensive flow diagram.

= Fast and Clear Visualization =

WP VisitorFlow not only tracks the flow of visitors on your WordPress website, it  makes the flow visible in your website's admin panel. Detailed but still clear diagrams provide you with the full information about the visitor flow. See how your visitors use your website. Learn how changes in your website's structure or new posts or pages influence the visitor flow. Use WP VisitorFlow to get feedback on your publishing actions and integrate it in your search engine optimization process.

= Highly Performant and Independent =

WP VisitorFlow is being developed with the focus on website performance, usability and data privacy. Although tremendous amounts of data can arise from the flow on highly frequented websites, the plugin is optimized for minimized data storage and a minimum database load. All data is stored in your WordPress database only - no third party tool or service is necessary. Last but not least, the software is being developed to fulfill strict data privacy regulations.

= Feature List =

* Storage of visitor data – Remote client’s IP address (encryption possible), user agent/web browser and operation system.
* Page views and visitor flow – Any page view on your WordPress website including date and time.
* Visualization of the visitor flow – Step-by-step diagrams providing at-a-glance information about the visitors’ routes on your website.
* Statistics on search engines, web crawlers, spiders and bots, including search key word lists.
* Encapsulated data storage – All data is stored only in your own WordPress database, no external source or additional service necessary. It is all yours, it stays yours.
* Data privacy – Optional anonymization of visitor data regarding data privacy rules in several countries.
* Data compression – Automatized compression (data aggregation) of older data to keep your data base lean and your website performance up.

= Support =

Please have also a look at the [Manual](https://www.datacodedesign.de/index.php/wp-visitorflow-manual/). 

In case of any problem with WP VisitorFlow, we are happy to help out. Please contact us under contact@datacodedesign.de. 

== Installation ==

= From Your WordPress Dashboard =

1. Visit “Plugins -> Add New”,
2. Search for “WP VisitorFlow”,
3. Install the plugin and activate it from your Plugins page.

= From WordPress.org = 

1. Download WP VisitorFlow from the plugin directory on wordpress.org,
2. Upload the folder to your “/wp-content/plugins/” directory using your favorite method (ftp, sftp, scp, etc…),
3. Activate WP VisitorFlow from your Plugins page.

= Once Activated =

You will find WP VisitorFlow in the menu of your admin panel. The default settings enable the recording of the visitor flow right away. If you want to go into detail and customize the recording process, please have a look at the settings and also see the [Manual](https://www.datacodedesign.de/index.php/wp-visitorflow-manual/). Have fun with WP VisitorFlow!

== Frequently Asked Questions == 

= What are the key features of this statistics plugin? =
* Detailed and clear visualization of the visitor flow on your website.
* Standalone plugin: No third party service or software necessary.
* Encapsulated data storage – All recorded data is yours and it stays yours.

= Why is no data collected? =
If no page view on your website is recorded by WP VisitorFlow please check the following:

* Is data recording activated under under VisitorFlow->Settings->General?
* Are you logged in as an administrator? If so, are page views by administrators excluded from data collection? This is the default setting, which can be changed under VisitorFlow->Settings->Recording.
* Have you visited only admin pages and are admin pages excluded from data collection? This is the default setting, which can be changed under VisitorFlow->Settings->Recording.
* Does your web browser submit a HTTP user agent string and are unknown user agents excluded from data collection? Please check the "Exclude empty UA strings" settings under VisitorFlow->Settings->Recording.

= How to exclude 404 error pages? =
By default also 404 error pages are recorded by WP VisitorFlow. You can exclude 404 pages from the recording in the settings section under VisitorFlow->Settings->Recording.

= What is the difference between "database start date"	and "flow data start date" in the summary? =
WP VisitorFlow stores the statistics data in two different ways: the "flow data" is very detailed because it contains information about the used web browsers, date and time of each visited page etc. Therefore, this flow data is stored only for a limited amount of time (typically 365 days; can be set under VisitorFlow->Settings->General). Older data is automatically deleted to keep the data base lean. Next to this, the flow data is automatically aggregated on a daily basis, which means that the total number of page views per post/page and the total number of referrer webpages are stored per day. This data is much smaller and is stored for an unlimited amount of time.

== Screenshots ==

1. Typical flow diagram provided by WP VisitorFlow showing the visitor flow. Here: the first two interactions with a website.
2. The overview page provides a summary of the recent number of visitors and page views.
3. Distribution of web browsers and operation systems used by remote clients.
4. Typical timeline of referrers, i.e. number of visitors coming from various search engines.
5. Android app available: see a summary of website visits for one or more WordPress installations on your Anbdroid mobile phone or tablet.

== Changelog ==
= 1.4 =
* Overhaul of the graphical user interface.
* The referrers summary in section "full webpage" shows now all search key words in the selected time interval.
* Update of the client devices database, e.g. identification of newest smartphones and tablets.
* Increase of default storage time from 30 days to 365 days for all flow data (can be changed in the settings).
* Bugfix: Corrected values in column "last visit" in table "Latest HTTP User-Agent-Strings"
* Bugfix: Corrected exclusion of pages containing strings listed in exclusion list
* Bugfix: Prevent double registration of mobile devices for data export to app.

= 1.3.1 =
* Bugfix: Corrected link to app in Google Play.

= 1.3 = 
* New: export function for statistics data as CSV rawdata tables. See new section "Data Export".
* New: access to statistics data via new mobile app ("WP VisitorFlow" available in Google Play app store). See new section "Data Export".
* Bugfix: in some (quite) special WordPress installations, users can be logged-in without any assigned role. Visits by such users are now recorded, too.

= 1.2.4 = 
* Update of the client devices database
* Translation update (German)

= 1.2.3 = 
* New diagram showing the development of visitors per day (see Full Website->Visitors).
* Bugfix for bot and exclusion counters

= 1.2.2 = 
* Visitor flow diagram: new option to filter the displayed data by browser, operation system or referrer page.
* New diagrams: distribution of referrer pages and visited pages over the hour of the day. 
* Total sum per day added to referrer pages and page view diagrams.

= 1.2.1 = 
* Flow diagram of the full website is now zoomable.
* Added German version. 
* Some minor bugfixes.

= 1.2 = 
* 404 error pages can be excluded from the statistics.

= 1.1.2 = 
* Small bugfix in visitor flow diagram.

= 1.1.1 = 
* Small bugfix in visitor flow diagram.

= 1.1 = 
* New: not only referrer websites but also user agents and operation sytems selectable for the visitor flow diagram (in "Full Website" view mode). This allows a fast visualization of the distrubition of user agents and operation systems next to the entry pages and the visitor flow on you websites.
* Fixed bug in displayed time differences.

= 1.0.4 =
* Implemented some quick links to the overview page, minor layout updates on some parts.

= 1.0.4 =
* Implemented some quick links to the overview page, minor layout updates on some parts.

= 1.0.3 =
* Corrected wrong display in search engine key word list.

= 1.0.1 =
* Banner and icon added for the WordPress plugin directory.

= 1.0 =
* First published version.

== Upgrade Notice ==
= 1.4 =
GUI overhaul + extended list of search key words + update of remote clients database + several bugfixes

= 1.3.1 =
New features: Data export as CSV rawdata tables and to mobile app. 

* 1.3
New features: Data export as CSV rawdata tables and to mobile app. 

= 1.2.4 = 
Update of the client devices database + some minor bugfixes

= 1.2.3 = 
New feature: New Diagram showing the development of visitors per day + some minor bugfixes

= 1.2.2 = 
New features: filter displayed data by browser type or operation system. New diagrams showing the distribution of referrer pages and visited pages over the hour of a day.

= 1.2.1 = 
* New feature: Flow diagram of the full website is now zoomable. Added German version. Some minor bugfixes.

= 1.2 =
New feature: 404 error pages can be excluded from the statistics. See new option in "Settings"->"Recording Settings", section "Exclude Wordpress Pages".

= 1.1.2 = 
Small bugfix in visitor flow diagram.

= 1.1.1 = 
Small bugfix in visitor flow diagram.

= 1.1 = 
New feature: also user agents and operation sytems selectable for the visitor flow diagram. Fixed bug in displayed time differences.

= 1.0.4 =
Implemented some quick links to the overview page, minor layout updates on some parts.

= 1.0.3 =
Corrected wrong display in search engine key word list.

= 1.0.1 =
No need for upgrade, just a new version related to the WP plugin directory.

= 1.0 =
First published version.
