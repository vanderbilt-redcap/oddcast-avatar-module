<?php
use REDCap;

$fp = fopen("php://output",'w');

$columnNames = [
	'stride_id',
	'institution_id',
	'project_id',
	'record_id',
	'instrument_id',
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
$records = $module->getRecords();
foreach($records as $record){
	$recordId = $record['record'];
	$instrument = $record['instrument'];

	$data = json_decode(REDCap::getData($module->getProjectId(), 'json', $recordId), true);
	$data = $data[0];

	if(!$headerRowPrinted){
		$printHeaderRow($data);
		$headerRowPrinted = true;
	}

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