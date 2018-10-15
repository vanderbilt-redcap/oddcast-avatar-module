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

<br>

<p>For a more granular/detailed series of events, see the "Analytics" report.</p>
<br>
<h5>General</h5>
<b>Time spent in review mode:</b> <?=$timeSpentInReviewMode?><br>
<b>Time spent in survey<?=$timeSpentInSurveySuffix?>:</b> <?=$module->getTimePeriodString($surveyCompleteLog['timestamp'] - $firstSurveyLog['timestamp'])?><br>
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

?>

<br>
<br>
<h5>Videos</h5>

<?php
if(empty($videoStats)){
	?><div>No videos were played.</div><?php
}
else{
	?>
	<table class="table table-striped table-bordered" style="width: auto">
		<tr>
			<th>Field Name</th>
			<th>Time Spent Playing</th>
			<th>Number of Plays</th>
		</tr>
		<?php
		foreach($videoStats as $field=>$stats){
			?>
			<tr>
				<td><?=$field?></td>
				<td class="text-right"><?=$module->getTimePeriodString($stats['playTime'])?></td>
				<td class="text-right"><?=$stats['playCount']?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
}

?>

<br>
<br>
<h5>Inline Descriptive Popups</h5>

<?php
if(empty($popupStats)){
	?><div>No inline popups were used.</div><?php
}
else{
	?>
	<table class="table table-striped table-bordered" style="width: auto">
		<tr>
			<th>Term</th>
			<th>Number of Views</th>
		</tr>
		<?php
		foreach($popupStats as $term=>$views){
			?>
			<tr>
				<td><?=$term?></td>
				<td class="text-right"><?=$views?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
}
