<?php
namespace Vanderbilt\OddcastAvatarExternalModule;
require_once 'header.php';

use Exception;

const TIMESTAMP_COLUMN = 'UNIX_TIMESTAMP(timestamp) as timestamp';

$module->runReportUnitTests();

$record = db_escape($_GET['record']);
$instrument = db_escape($_GET['instrument']);

list(
	$firstReviewModeLog,
	$firstSurveyLog,
	$surveyCompleteLog,
	$avatarUsagePeriods,
	$videoStats,
	$popupStats,
) = $module->analyzeSurvey($record, $instrument);

if($firstReviewModeLog) {
	$timeSpentInReviewMode = $module->getTimePeriodString($firstSurveyLog['timestamp'] - $firstReviewModeLog['timestamp']);
	$timeSpentInSurveySuffix = ' (after review mode)';
} else {
	$timeSpentInReviewMode = 'Review mode was not enabled';
	$timeSpentInSurveySuffix = '';
}

?>

<style>
	.table{
		width: auto;
		margin-top: 15px;
		margin-bottom: 30px;
	}
</style>

<p>This report summarizes several event types.  For a more granular/detailed series of events, see the "Analytics" report.</p>

<div><b>Record:</b> <?=$record?></div>
<div><b>Instrument:</b> <?=$instrument?></div>
<br>
<b>Time spent in review mode:</b> <?=$timeSpentInReviewMode?><br>
<b>Time spent in survey<?=$timeSpentInSurveySuffix?>:</b> <?=$module->getTimePeriodString($surveyCompleteLog['timestamp'] - $firstSurveyLog['timestamp'])?><br>
<br>
<?php

$module->displayAvatarStats($avatarUsagePeriods);
$module->displayVideoStats($videoStats);
$module->displayPopupStats($popupStats);
