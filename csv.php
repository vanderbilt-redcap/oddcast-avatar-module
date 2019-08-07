<?php
$fp = fopen("php://output",'w');
fputcsv($fp, [
	'url',
	'project_id',
	'record_id',
	'instrument'
]);

$records = $module->getRecords();
foreach($records as $record){
	$recordId = $record['record'];
	$instrument = $record['instrument'];

	list(
		$firstReviewModeLog,
		$firstSurveyLog,
		$lastSurveyLog,
		$avatarUsagePeriods,
		$videoStats,
		$popupStats,
	) = $module->analyzeSurvey($recordId, $instrument);

	fputcsv($fp, [
		'',
		$module->getProjectId(),
		$recordId,
		$instrument
	]);
}