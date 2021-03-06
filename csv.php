<?php
use REDCap;

$fp = fopen("php://output",'w');

$columnNames = [
	'session_id',
	'institution_id',
	'project_id',
	'stride_id',
	'record_id',
	'instrument_id',
	'session_start',
	'visit_dt',
	'refused',
	'race',
	'race_oth',
	'ethnicity',
	'gender',
	'age',
	'consent_mode',
	'race_ra',
	'race_oth_ra',
	'ethnicity_ra',
	'gender_ra',
	'age_ra',
	'avatar_id_1',
	'avatar_seconds_1',
	'avatar_id_2',
	'avatar_seconds_2',
	'avatar_id_3',
	'avatar_seconds_3',
	'avatar_seconds_other',
	'avatar_disabled',
	'popups_viewed',
	'consent_viewed',
	'consent_time',
	'consented',
	'retention',
];

$printHeaderRow = function($data) use ($fp, &$columnNames){
	foreach($data as $fieldName=>$value){
		if(strpos($fieldName, 'procedures_consented___') === 0){
			$columnNames[] = $fieldName;
		}
	}

	fputcsv($fp, $columnNames);
};

$getVideoFieldNumber = function($fieldName){
	$parts = explode('_', $fieldName);
	$number = @$parts[1];
	if(count($parts) === 2 && $parts[0] === 'video' && ctype_digit($number)){
		return $number;
	}
	else{
		return null;
	}
};

$videoFieldNames = [];
foreach(REDCap::getFieldNames() as $fieldName){
	$videoNumber = $getVideoFieldNumber($fieldName);
	if($videoNumber){
		$columnNames[] = "video_url_$videoNumber";
		$columnNames[] = "video_seconds_$videoNumber";
		$videoFieldNames[] = $fieldName;
	}
}

$domainName = $_SERVER['HTTP_HOST'];
$projectId = $module->getProjectId();

$headerRowPrinted = false;
$sessions = $module->getSessionsForDateParams();
foreach($sessions as $session){
	$recordId = $session['record'];
	$instrument = $session['instrument'];

	$data = json_decode(REDCap::getData($module->getProjectId(), 'json', $recordId), true);
	$data = $data[0];

	if(!$headerRowPrinted){
		$printHeaderRow($data);
		$headerRowPrinted = true;
	}

	list(
		$firstReviewModeLog,
		$firstSurveyLog,
		$lastSurveyLog,
		$avatarUsagePeriods,
		$videoStats,
		$popupStats,
	) = $module->analyzeLogEntries($session['logs'], $session['instrument']);

	$sessionStart = date('Y-m-d-H-i', $firstSurveyLog['timestamp']);
	$data['session_id'] = "$domainName-$projectId-$recordId-$instrument-" . $sessionStart;
	$data['institution_id'] = $domainName;
	$data['project_id'] = $projectId;
	$data['record_id'] = $recordId;
	$data['instrument_id'] = $instrument;
	$data['session_start'] = $sessionStart;

	$module->setAvatarAnalyticsFields($avatarUsagePeriods, $data);

	foreach($videoFieldNames as $fieldName){
		$videoNumber = $getVideoFieldNumber($fieldName);
		$data["video_url_$videoNumber"] = @$module->getVideoUrl($fieldName);
		$data["video_seconds_$videoNumber"] = @$videoStats[$fieldName]['playTime'];
	}

	$data['popups_viewed'] = 0;
	foreach($popupStats as $term => $count){
		$data['popups_viewed'] += intval($count);
	}

	$data['consent_time'] = $lastSurveyLog['timestamp'] - $firstSurveyLog['timestamp'];
	$data['consented'] = $module->isInstrumentComplete($recordId, $instrument);

	$exportData = [];
	foreach($columnNames as $columnName){
		$exportData[] = $data[$columnName];
	}

	fputcsv($fp, $exportData);
}