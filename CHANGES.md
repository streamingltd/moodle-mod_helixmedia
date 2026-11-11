### Releases ###

#### 2015070701 ####
- Supports Moodle 2.6 to 2.9.
- Modal dialog now works on the iPad (note this fix will also apply to the Assignment submission/feedback upload).
- Maximum dialog size increased.
- Minor language improvements on the global settings page.

#### 2015070702 ####
- Supports Moodle 2.5 only.
- Special release to fix Moodle 2.5 bug in 2015070702.

#### 2015090202 ####
- Supports Moodle 2.6 to 2.9.
- Improved the modal dialogue display on tablets (applies to Assingment as well).
- Added code to handle the new dynamically sized video option in Medial.
- Moodle 2.6 is now the minimum version.

#### 2015102001 ####
- Supports Moodle 2.6 to 2.9.
- Branding change to Medial.

#### 2015112502 ####
- Supports Moodle 2.7 to 3.0.
- Logging now uses the Moodle events system.
- Video Iframe height increased.

#### 2016011501 ####
- Supports Moodle 2.7 to 3.0.
- Fixes some PHP notices.

#### 2016042601 ####
- Supports Moodle 2.7 to 3.0.
- Fix to correctly display thumbnails when assignment submissions are cancelled

#### 2016051601 ####
- Supports Moodle 2.7 to 3.0.
- Fix the line endings in LTI requests so they are always CRLF

#### 2016060701 ####
- Supports Moodle 2.7 to 3.1.
Moodle 3.1 compatibility fixes.
Fixes for Submission and Feedback launch.

#### 2016090603 ####
- Supports Moodle 2.7 to 3.1
- Moodle 3.1 global seach support added
- Upload button text changed to "Add media"
- Fix crash when using PHP 7

#### 2017013101 ####
- Supports Moodle 2.7 to 3.3.
- The site title and mod intro text is now included in LTI requests.
- Moodle activity completion tracking now supported (Moodle 3.0+ only).
- Dialog sizes have been adjusted for mobile devices.
- Tested with Moodle 3.2.

#### 2018080802 ####
- Supports Moodle 3.1 to 3.3.
- Tested with Moodle 3.3, minimum version now 3.1.
- Fix a problem determining instructor status when using ATTO.

#### 2018090704 ####
- Supports Moodle 3.1 to 3.5.
- Tested with Moodle 3.4 and 3.5.
- Improvements to the iframe sizing.
- Better handling of audio only media.
- Fixed a bug in the module viewed event.
- Fixed playback bug in IE 11.

#### 2018121804 ####
- Supports Moodle 3.1 to 3.6.
- Tested with Moodle 3.6.
- Allow the use of the camera and mic in the dialog so html5 webam capture works.
- New config settings to make debugging of LTI problems easier.

#### 2019052102 ####
- Supports Moodle 3.1 to 3.7.
- Tested with Moodle 3.7.
- Fix for group assignment submission problem.
- Fix for assignment submission problem in Moodle 3.7.
- Fix for moodle search class name change.

#### 2020012101 ####
- Supports Moodle 3.5 to 3.8.
- Tested with Moodle 3.8.
- Legacy cron converted to use tasks API.
- Removed the repository plugin migration tool.
- Minimum supported Moodle version is now 3.5.

#### 2020111701 ####
- Supports Moodle 3.5 to 3.10.
- Tested with Moodle 3.9 and 3.10.
- Now using HTML 5 doc types.
- Always Learner role for student submissions, even if the user is a teacher.
- Support for MoodleMobile.
- Fixes for LTI URL handling.
- Restrict LTI resource desctiption to 1000 chars.
- Prevent MEDIAL version check call failure from blocking the upgrade.

#### 2022012501 ####
- Supports Moodle 3.9 to 4.0.
- Tested with Moodle versions up to 4.0dev.
- Fully responsive video embedding.
- Convert Javascript to AMD Module.
- Code clean up, convert to use mustache based templates.
- Add launch spinner.
- Use standard bootstrap modal for video selection.

#### 2022112101 ####
- Supports Moodle 3.9 to 4.2.
- Upload button text now translateable.
- Improvements to responsive video embedding.
- MoodleMobile bugfixes and session detection.

#### 2023100302 ####
- Supports Moodle 3.9 to 4.2.
- Always send real LTI role to MEDIAL.
- Supports Moodle Fixes for Ultra-wide screens.
- Add display capture permissions to iframes to allow use with MediaLecture Web.
- Add fullscreen permissions to the Modal iframes
- Tested with MEDIAL 8.5

#### 8.5.10 (2024012301) ####
- Supports Moodle 3.9 to 4.3.
- Tested with MEDIAL 8.5.10.
- Fixes for Moodle 4.3 compatibility.
- Moodle Dashboard permission added.
- Fix for crash when using Postgres DB and the MoodleMobile app.
- Use standard Moodle dialogues instead of our custom one for embeds enbled via the filter to improve theme compatibility.
- Use Moodle templates for legacy embed resize code.

#### 8.5.019e (2024120901) #### 
- Supports Moodle 4.1 to 4.5.
- Tested with MEDIAL 8.5.019e and Moodle 4.5.
- Fixes for PHP 8.3 dynamic property warnings.
- Fix for mobile App rendering in Moodle 4.2+ and module-info display.
- Fix crash restoring a course with Postgres.
- Add privacy provider.
- Fix crash when using default activity completion page.
- Legacy search index code removed.
- Fix for issues when using ATTO/Tiny in student assignments.
- Add Monologo.

#### 9.0.003i (2025112401) ####
- Supports Moodle 4.1 to 5.0.
- Support for Moodle 5.0, Bootstrap 5 and PHP 8.4.
- Added support for using LTI 1.3 to connect to MEDIAL.
- Make the activity gradeable and support LTI grading (LTI 1.3 only) for use with MEDIAL quizzes.
- Github workflows added to run basic moodle CI checks.
- Fix for mobile app launch bouncing users to the login.
- Fix a bug in the way access restrctions are checked.

#### 9.1.000 (2026052002) ####
- Supports Moodle 4.5 to 5.2
- Tested with Moodle 5.1 and 5.2
- Update modal dialogue code to remove legacy modal factory
- Improve launch data security
- Fix activity view permissions check
