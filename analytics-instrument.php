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
	body{
		max-width: 1200px;
	}

	.table{
		width: auto;
		margin-top: 15px;
		margin-bottom: 0px;
	}

	td{
		overflow: hidden;
		max-width: 290px;
		text-overflow: ellipsis;
	}
</style>

<p>This report summarizes several event types.  For a more granular/detailed series of events, see the "Analytics" report.</p>
<br>

<div class="row">
	<div class="col-12 col-lg-6">
		<table>
			<tr>
				<th>Record ID:</th>
				<td><?=$record?></td>
			</tr>
			<tr>
				<th>Instrument:</th>
				<td><?=$instrument?></td>
			</tr>
			<tr>
				<th>Time spent in review mode:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
				<td><?=$timeSpentInReviewMode?></td>
			</tr>
			<tr>
				<th>Time spent in survey<?=$timeSpentInSurveySuffix?>:</th>
				<td><?=$module->getTimePeriodString($surveyCompleteLog['timestamp'] - $firstSurveyLog['timestamp'])?></td>
			</tr>
		</table>

		<br>
		<br>

		<?php
		$module->displayVideoStats($videoStats);
		?>
		<br>
		<br>
		<?php
		$module->displayPopupStats($popupStats);
		?>
		<br>
		<br>
	</div>
	<div class="col-12 col-lg-6"><?php $module->displayAvatarStats($avatarUsagePeriods); ?></div>
</div>
