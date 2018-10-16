<?php
namespace Vanderbilt\OddcastAvatarExternalModule;
require_once 'header.php';

use Exception;

const TIMESTAMP_COLUMN = 'UNIX_TIMESTAMP(timestamp) as timestamp';

$module->runReportUnitTests();

$record = db_escape($_GET['record']);
$instrument = db_escape($_GET['instrument']);

?>

<style>
	.table{
		width: auto;
	}
</style>

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
<p>Periods during which an avatar was enabled or disabled (in order):</p>

<?php

if(empty($avatarUsagePeriods)){
	echo "None";
}
else{
	?>
	<style>
		table.avatar tr:not(:first-child),
		table.avatar td.character img{
			height: 100px;
		}

		td.character{
			padding: 0px;
		}

		td.character img{
			border-top: 4px solid white;
		}
	</style>
	<table class="table table-striped table-bordered avatar">
		<tr>
			<th>Status</th>
			<th>Length of Time</th>
			<th>Character</th>
			<th>Gender</th>
		</tr>
		<?php
		foreach($avatarUsagePeriods as $avatar){
			$showId = $avatar['show id'];

			if($avatar['initialSelectionDialog']){
				$status = 'Initial selection<br>dialog displayed';
			}
			else if($avatar['disabled']){
				$status = 'Disabled';
			}
			else{
				$status = 'Enabled';
			}

			?>
			<tr>
				<td class="align-middle"><?=$status?></td>
				<td class="align-middle"><?=str_replace(', ', '<br>and ', $module->getTimePeriodString($avatar['end'] - $avatar['start']))?></td>

				<?php if($avatar['disabled'] || $avatar['initialSelectionDialog']) { ?>
					<td></td>
					<td></td>
				<?php } else { ?>
					<td class="character"><img src="<?=$module->getUrl("images/$showId.png")?>"</td>
					<td class="align-middle"><?=ucfirst(OddcastAvatarExternalModule::SHOWS[$showId])?></td>
				<?php } ?>
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
<h5>Videos</h5>

<?php
if(empty($videoStats)){
	?><div>No videos were played.</div><?php
}
else{
	?>
	<table class="table table-striped table-bordered">
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
	<table class="table table-striped table-bordered">
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
