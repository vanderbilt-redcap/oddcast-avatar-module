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

$getLog = function($otherWhereClauses) use ($module, $record, $instrument){
	$sql = "
		select
			log_id,
			" . TIMESTAMP_COLUMN . "
		where
			record = '$record'
			and instrument = '$instrument'
			and $otherWhereClauses
	";

	$result = $module->queryLogs($sql);

	$row = db_fetch_assoc($result);
	$row2 = db_fetch_assoc($result);

	if($row2 !== null){
		throw new Exception("Found more than one row for SQL: $sql");
	}

	return $row;
};


$firstLog = $getLog("
	message = 'survey page loaded'
	and page = 1
");

$lastLog = $getLog("
	message = 'survey complete'
");

$avatarUsagePeriods = $module->getAvatarUsagePeriods($record, $firstLog, $lastLog);

?>

<br>

This report is very basic and should be made more user friendly.<br>
<p>For a more granular/detailed series of events, see the "Analytics" report.</p>
<br>
<h5>General</h5>
<b>Time user spent in survey:</b> <?=$module->getTimePeriodString($lastLog['timestamp'] - $firstLog['timestamp'])?><br>
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

		$avatarEnd = @$avatar['end'];
		if(empty($avatarEnd)){
			// They must have left the avatar on until the end of the survey.
			$avatarEnd = $lastLog['timestamp'];
		}

		$seconds = $avatarEnd - $avatar['start'];
		$timePeriodString = $module->getTimePeriodString($seconds);

		echo "#$showNumber - $gender, race TBD, $timePeriodString<br>";
	}
}
