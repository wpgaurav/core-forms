Changelog
==========

#### 1.2.0 - July 31, 2024 

- Added new "Require Users to Be Logged In?" feature.
- Fix for Form Preview when Submission Limit has been reached.
- Changed "Export to CSV" separator to be a comma, instead of a tab, for better compatibility with Excel.
- Improved handling of file upload fields in the "Export to CSV" feature.
- Added form ID to "Export to CSV" filename.

#### 1.1.0 - June 24, 2024 

- Added new Submission Limit feature.

#### 1.0.17 - May 20, 2024 

- Interface changes.

#### 1.0.16 - Feb 29, 2024 

- Update copyright year.
- Fix plugin updates not working.
- Very minor performance improvement.


#### 1.0.14 - Oct 20, 2023

- PSR-12 codestyle adherance.
- Accessibility improvements for file upload action settings form.
- Minor performance improvements.


#### 1.0.13 - May 14, 2022

- Add nonce to all URL's using `_hf_admin_action`


#### 1.0.12 - Feb 16, 2021

- Minor improvements to logic for handling file uploads.


#### 1.0.11 - Jan 13, 2021

**Improvements**

- Performance improvements for bootstrapping code.
- Change domain to htmlformsplugin.com.
- Updated internal dependencies.


#### 1.0.10 - Apr 6, 2020

**Fixes**

- Generate metadata for uploades files so media editor can be used and files have attachments. 
- Add random component to generated filenames so uploaded files aren't as easily guessed or iterated upon.


#### 1.0.9 - Oct 18, 2019

**Fixes**

- Issue with "undefined new submissions" showing when using form notifications.


#### 1.0.8 - September 17, 2019

**Improvements**

- Set HTTP referer in webhook action.


#### 1.0.7 - February 26, 2019

**Improvements**

- Add message setting for general file upload errors.
- Show notice when free HTML Forms plugin is not installed and activated.


#### 1.0.5 - November 6, 2018

**Improvements**

- Validate file uploads before form processing, so that we can show error messages.
- Add message setting for "file too large" errors.


#### 1.0.4 - November 5, 2018

**Fixes**

- Bulk deleting submissions will mark the submissions as seen automatically

**Improvements**

- Ensure deleted submissions never cause the notification badge to show-up

**Additions**

- Add filter `hf_upload_add_to_media` to allow disabling whether file uploads show up in WP Admin Media


#### 1.0.3 - Sep 11, 2018

**Fixes**

- PHP notice in notifications plugin.

**Changes**

- Webhooks now send the raw form data instead of the submission object.


#### 1.0.2 - April 9, 2018

**Additions**

- Added support for file uploads.


#### 1.0.1 - March 28, 2018

**Additions**

- Added update checks so that plugin updates can be installed with an active plugin license.


##### 1.0 - March 2018

Initial release. This plugin requires [HTML Forms](https://wordpress.org/plugins/html-forms/) to work.
