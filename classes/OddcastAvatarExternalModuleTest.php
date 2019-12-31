<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

class OddcastAvatarExternalModuleTest{
    function __construct($module){
        $this->module = $module;
    }

    function runReportUnitTests(){
        
        $instrument = 'some_instrument';

        $logs = [
            ['message' => 'survey page loaded', 'instrument' => $instrument, 'page' => '1'],
            ['message' => 'some other event',   'instrument' => $instrument],
            ['message' => 'survey page loaded', 'instrument' => $instrument, 'page' => '2'],
            ['message' => 'survey page loaded', 'instrument' => $instrument, 'page' => '1'],
            ['message' => 'survey complete',    'instrument' => $instrument],
		];

        $logs = $this->flushOutMockLogs($logs);
        
		list(
            $firstReviewModeLog,
            $firstSurveyLog,
            $lastSurveyLog,
            $avatarUsagePeriods,
            $videoStats,
            $popupStats,
            $pageStats
        ) = $this->analyzeLogEntries($logs, $instrument);

		$this->assertSame([
            '1' => ['seconds' => 3],
            '2' => ['seconds' => 1]
        ], $pageStats);
    }

    function __call($name, $arguments){
		return call_user_func_array([$this->module, $name], $arguments);
	}
}