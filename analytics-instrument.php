<?php
namespace Vanderbilt\OddcastAvatarExternalModule;
require_once 'header.php';

use Exception;

const TIMESTAMP_COLUMN = 'UNIX_TIMESTAMP(timestamp) as timestamp';

$module->runReportUnitTests();

$record = db_escape($_GET['record']);
$instrument = db_escape($_GET['instrument']);

?>

<div><b>Record:</b> <?=$record?></div>
<div><b>Instrument:</b> <?=$instrument?></div>

<?php

list(
	$firstReviewModeLog,
	$firstSurveyLog,
	$surveyCompleteLog,
	$avatarUsagePeriods
) = $module->analyzeSurvey($record, $instrument);

?>

<br>

This report is very basic and should be made more user friendly.<br>
<p>For a more granular/detailed series of events, see the "Analytics" report.</p>
<br>
<h5>General</h5>
<?php if($firstReviewModeLog) { ?>
	<b>Time spent in review mode:</b> <?=$module->getTimePeriodString($firstSurveyLog['timestamp'] - $firstReviewModeLog['timestamp'])?><br>
<?php } else { ?>
	<b>Review mode not enabled</b><br>
<?php } ?>
<b>Time spent in survey (after review mode):</b> <?=$module->getTimePeriodString($surveyCompleteLog['timestamp'] - $firstSurveyLog['timestamp'])?><br>
<br>
<h5>Avatar</h5>
<h6>Periods during which an avatar was enabled:</h6>

<?php

if(empty($avatarUsagePeriods)){
	echo "None";
}
else{
	foreach($avatarUsagePeriods as $avatar){
		list($showNumber, $gender) = $module->getShowDetails($avatar['show id']);

		$seconds = $avatar['end'] - $avatar['start'];
		$timePeriodString = $module->getTimePeriodString($seconds);

		echo "#$showNumber - $gender, race TBD, $timePeriodString<br>";
	}
}