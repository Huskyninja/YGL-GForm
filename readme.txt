=== Plugin Name ===
Plugin Name: You've Got Leads Gravity Forms
Description: Send form data to the You've Got Leads CRM using Gravity Form's Add-on Framework
version: 0.13
Author: Husky Ninja
Author URI: https://www.husky.ninja
License: GPLv3 or later
Text Domain: ygl-gform
Domain Path: /languages

Sends Gravity Form data to You've Got Leads /leads RESTful endpoint.

== Description ==

For use only with Gravity Forms v1.9 or greater.

Main Settings

Main for settings can be found under admin -> Forms -> Settings -> YGL GForm. You will need a YGL username and password. The base endpoint url may be edited if necessary.

You may need to enter a LeadSourceName, LeadSourceID, and LeadSourceRank. Leave the default values if you have not received any information from YGL.

Sending a Debug Email

You can send a debug email for all submissions that contain logging information which can be useful if you do not have logging enabled. Select "Send a debug email" to enable this feature, and enter a valid email under "Debug email address". This will send an email containing logging information for all forms submitted to You've Got Leads.

Global Submission Bypass

The Global Submission Bypass is designed to allow your forms to bypass submission to YGL when a form condition is met. If a user selects a value from the field that has been mapped to Form Bypass Keyword (which is limited to the select field type) and that selection's value matches the Global Bypass Keyword, then the form will not be submitted to YGL. The form will still submit to the site, and any Wordpress / Gravity Form processes will continue as normal.

This feature can be useful if you have a condition that a user must meet in order to be submitted to YGL (leads v. employment seekers for example). It is important that the value of the option within the select field (in the Field Properties, select the "Show Values" checkbox) be set to match the Global Bypass Keyword exactly. Please note, the input is "trimmed" before the comparison is run to ensure any leading or trailing spaces are ignored.

If the checkbox Use Global Submission Bypass is unchecked, no interruption of YGL submission will take place, and any fields mapped to Form Bypass Keyword will do nothing. Fields mapped to Form Bypass Keyword do not send information to YGL on their own. Form fields may be mapped multiple times, so the Form Bypass Keyword should not interfere with YGL submissions unless specifically directed.

Form Settings

Individual form settings can be found under admin -> Forms -> Forms -> {form name} -> Settings -> YGL GForm.

Select the "Send this form to You've Got Leads" checkbox to attach the form. You will need to set the Community ID, as the default value is only a placeholder and will not work.

By default this plugin uses Remote Post (wp_remote_post) to send form data. This can be changed to to use cURL. If you have cURL installed and wish to use this method, select this checkbox.

You can set a custom value for the Lead Source Name, Lead Source ID and LeadSourceRank. This value will overwrite the global Lead Source Name, Lead Source ID or Lead Source Rank set on the plugin's configuration screen. Take care when setting these values as any mismatches will cause the request to fail. Also, it is important to ensure that your YGL account has permissions to access these values remotely.

Field Mapping

To map the form fields, select the relevant Field (to be mapped for YGL) to the Form Field (from the Gravity Form).

The form field must be of the correct type. The mapping is as follows:

First Name -> name, text or hidden
Last Name -> name, text or hidden
Email Address -> email or hidden
Phone -> phone or hidden
Street Address -> address, text or hidden
Address Line 2 -> address, text or hidden
City -> address, text or hidden
State -> address, text or hidden
Zip -> address, text or hidden
Country -> address, text or hidden
Community -> select
Form Bypass Keyword -> select

So make sure when creating your form that you use the correct form field types for the YGL field mapping.

Important: When using the Gravity Forms Address field, be sure to map the correct matching address sub-field. For example, Address Line 2 should be mapped to Address Line 2.

If you map the Community field, this value will overwrite the required Community ID for the form. This field is provided to allow for multiple communities to be assigned to a single form (and selected by an end user). When mapping this field, please ensure that the value of the field(s) is set to a YGL Community ID. Please note that the Community ID is still a required field in the form's settings.

Form Bypass Keyword is used with the Global Submission Bypass value. Be sure that the value of this field matches the Global Bypass Keyword. See the Global Bypass Submission section (above) for more information.

== Changelog ==

= 0.13 =
* added method to conditionally bypass submission to YGL through user dropdown selection & field mapping (Global Submission Bypass)
* updated css for headlines on admin / instructions page

= 0.12 =
* added support for street address, including most sub-fields from the GForm address field type

= 0.11 =
* added LeadSourceRank to form level customization
* keep improving help docs

= 0.10.1 =
* updated instruction & help documentation
* finally fixed scripts and styles references

= 0.10 =
* added a form level customization for LeadSourceName and LeadSourceID
* added mappable ComminityID field
* fixed unidentified index issue with cURL selection value

= 0.9 =
* added debug email feature
* added error reporting for cURL
* improved error reporting & logging for Remote Post

= 0.8 =
* changed default method for posting to Wordpress Remote Post with cURL as a selectable alternative

= 0.7 =
* fixed typo in logging response data from curl
* added validation to the YGL endpoint URL to avoid incorrect entry (and a dead connection) by requiring 'https:'

= 0.6 =
* fixed email not being applied at the creation of the connection (email not sending)
* fixed sageage url in plugin description

= 0.5 =
* fixed illegal offset issue when not sending form

= 0.4 =
* fixed referral source section of JSON string
* added instruction page

= 0.3 =
* add referral source values

= 0.2 =
* fix Uninitialized string offset: 0 and Illegal string offset 'send_form' issue when loading send_form for a form that has not been configured to use YGL

= 0.1 =
* First buildout.

== Upgrade Notice ==

= 0.0 =
Placeholder.

== Things to do ==

== Arbitrary section ==

This is arbitrary.