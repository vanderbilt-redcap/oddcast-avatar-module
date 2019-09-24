<?php
namespace Vanderbilt\OddcastAvatarExternalModule;
require_once 'header.php';

use Exception;

$module->runReportUnitTests();

$sessions = $module->getSessionsForLogIdParams();
$firstSession = $sessions[0];

list(
	$firstReviewModeLog,
	$firstSurveyLog,
	$lastSurveyLog,
	$avatarUsagePeriods,
	$videoStats,
	$popupStats,
) = $module->analyzeLogEntries($firstSession['logs'], $firstSession['instrument']);

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

	table#general-info th{
		padding: 3px;
		padding-right: 15px;
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

<p>This report summarizes several event types.  Events during Review Mode are not included in this report.  For a more granular/detailed series of events, see the "Analytics" report.</p>
<br>

<div class="row">
	<div class="col-12 col-lg-6">
		<table id="general-info">
			<tr>
				<th>Record ID:</th>
				<td><?=$firstSurveyLog['record']?></td>
			</tr>
			<tr>
				<th>Instrument:</th>
				<td><?=$firstSurveyLog['instrument']?></td>
			</tr>
			<tr>
				<th>Time spent in review mode:</th>
				<td><?=$timeSpentInReviewMode?></td>
			</tr>
			<tr>
				<th>Time spent in survey<?=$timeSpentInSurveySuffix?>:</th>
				<td><?=$module->getTimePeriodString($lastSurveyLog['timestamp'] - $firstSurveyLog['timestamp'])?></td>
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
