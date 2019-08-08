<?php
use REDCap;

$fp = fopen("php://output",'w');

$columnNames = [
	'stride_id', // maybe combine other ids
	'institution_id', // maybe exclude since included in stride id
	'project_id', // maybe exclude since included in stride id
	'record_id', // maybe exclude since included in stride id
	'instrument_id', // maybe exclude since included in stride id
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
	'avatar_selected_yn',
	'avatar_selected',
	'avatar_disabled',
	'avatar_seconds',
	'video_url_1',
	'video_seconds_1',
	'video_url_2',
	'video_seconds_2',
	'video_url_3',
	'video_seconds_3',
	'video_url_4',
	'video_seconds_4',
	'popups_viewed',
	'consent_viewed',
	'consent_time',
	'procedures_consented',
	'consented',
	'retention',
];

fputcsv($fp, $columnNames);

$domainName = $_SERVER['HTTP_HOST'];
$projectId = $module->getProjectId();

$records = $module->getRecords();
foreach($records as $record){
	$recordId = $record['record'];
	$instrument = $record['instrument'];

	$data = json_decode(REDCap::getData($module->getProjectId(), 'json', $recordId), true);
	$data = $data[0];

	$data['stride_id'] = "$domainName-$projectId-$recordId-$instrument";
	$data['institution_id'] = $domainName;
	$data['project_id'] = $projectId;
	$data['record_id'] = $recordId;
	$data['instrument_id'] = $instrument;

	list(
		$firstReviewModeLog,
		$firstSurveyLog,
		$lastSurveyLog,
		$avatarUsagePeriods,
		$videoStats,
		$popupStats,
	) = $module->analyzeSurvey($recordId, $instrument);

	$data['avatar_selected_yn'] = 0;
	$data['avatar_disabled'] = 0;
	$data['avatar_seconds'] = 0;
	$longestPeriod = null;
	foreach($avatarUsagePeriods as $period){
		if($period['initialSelectionDialog']){
			continue;
		}

		if($period['disabled']){
			$data['avatar_disabled'] = 1;
		}
		else{
			$data['avatar_selected_yn'] = 1;
			$data['avatar_seconds'] = 5;
			$data['avatar_seconds'] += $period['end'] - $period['start'];
			$longestPeriod = $module->getLongestPeriod($period, $longestPeriod);
		}
	}

	$data['avatar_selected'] = null;
	if($longestPeriod){
		$data['avatar_selected'] = $longestPeriod['show id'];
	}

	for($i=0; $i<=4; $i++){
		$fieldName = "video_$i";
		$data["video_url_$i"] = @$module->getVideoUrl($fieldName);
		$data["video_seconds_$i"] = @$videoStats[$fieldName]['playTime'];
	}

	$data['popups_viewed'] = 0;
	foreach($videoStats as $term => $count){
		$data['popups_viewed'] += intval($count);
	}

	$data['consent_time'] = $lastSurveyLog['timestamp'] - $firstReviewModeLog['timestamp'];
	$data['consented'] = $module->isInstrumentComplete($recordId, $instrument);

	$exportData = [];
	foreach($columnNames as $columnName){
		$exportData[] = $data[$columnName];
	}

	fputcsv($fp, $exportData);
}