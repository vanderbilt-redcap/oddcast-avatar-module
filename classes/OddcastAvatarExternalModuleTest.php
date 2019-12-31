<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use Exception;

class OddcastAvatarExternalModuleTest{
    function __construct($module){
        $this->module = $module;
    }

    function runReportUnitTests(){
        $this->testGetTimePeriodString();
        $this->testPageStats();
    }

    private function testGetTimePeriodString()
	{
		$assert = function($expected, $seconds){
			$actual = $this->getTimePeriodString($seconds);
			if($expected !== $actual){
				throw new Exception("Expected '$expected' but got '$actual'!");
			}
		};

		$assert('0 seconds', 0);
		$assert('1 second', 1);
		$assert('2 seconds', 2);
		$assert('59 seconds', 59);
		$assert('1 minute, 0 seconds', 60);
		$assert('1 minute, 1 second', 61);
		$assert('1 minute, 2 seconds', 62);
		$assert('1 minute, 59 seconds', 60+59);
		$assert('2 minutes, 0 seconds', 60*2);
	}

    private function testPageStats(){
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