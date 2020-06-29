# The Oddcast Avatar Module
This module makes surveys more interactive by adding an "avatar" character to the page that can read messages to the participant at specific times.  It was developed for and is specifically tailored to the needs of the STRIDE grant.  Some features include:

* **Page Messages** - Have the avatar read messages to the user when certain pages are loaded
* **Message for field values** - Have the avatar read messages to the user when certain values are selected
* **Review Mode** - Step through the pages of a survey for review/demonstration purposes while skipping required fields before actually beginning the survey.  Analytics data captured during **Review Mode** is still included in all reports.
* **Survey Timeout** - Restart the survey after a certain amount of time is elapsed to protect sensitive data if users walk away from the screen

## Analytics Reporting
This module adds an **Avatar Analytics** report link in the **External Modules** section of the left menu.  This report aggregates and summarizes the raw analytics data provided by the **Analytics** module & report, as well as additional avatar related data.  Projects must adhere to all agreed upon standards to ensure that all analytics data is captured appropriately.  These standards include but are not limited to the following:
* General REDCap good practices
* The **Analytics** module must be enabled at all times in addition to this module to capture all required analytics data.
* Videos must have variable names likes **video_1**, **video_2**, etc. to be included by the **Export In Repository Format** feature.  This was agreed upon to simplify the process of comparing video related metrics between different projects.  If a video is updated or replaced, a new **video_#** variable name should be used in order to distinguish between analytics data for the old vs. new videos.  There is no limit to the number of videos.
* The following field variable names are the only non-analytics data currently set up to come through automatically when using the **Export In Repository Format** feature:
  * visit_dt
  * refused
  * race
  * race_oth
  * ethnicity
  * gender
  * age
  * consent_mode
  * race_ra
  * race_oth_ra
  * ethnicity_ra
  * gender_ra
  * age_ra
  * procedures_consented
  * retention
