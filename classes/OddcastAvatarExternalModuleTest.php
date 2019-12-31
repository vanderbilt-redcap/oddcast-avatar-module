<?php
namespace Vanderbilt\OddcastAvatarExternalModule;

use Exception;

class OddcastAvatarExternalModuleTest{
    function __construct($module){
        $this->module = $module;
    }

    function runReportUnitTests(){
        $this->testGetTimePeriodString();
        $this->testAnalyzeLogEntries_basics();
        $this->testAnalyzeLogEntries_video();
        $this->testGetSessionsFromLogs();
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

    private function testAnalyzeLogEntries_basics()
	{
		$instrument = 'instrument1';

		$logs = [
			['message' => 'survey page loaded', 'instrument' => $instrument],
            ['message' => 'survey page loaded', 'instrument' => $instrument],
		];

		$logs = $this->flushOutMockLogs($logs);

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods
		) = $this->analyzeLogEntries($logs, $instrument);

		$this->assertSame(1, $lastSurveyLog['timestamp'] - $firstSurveyLog['timestamp']);
	}

	private function testAnalyzeLogEntries_video()
	{
		// test no video messages
		$this->assertVideoStats(
			[],
			[]
		);

		// test all messages that stop play
		$this->assertVideoStats(
			[
				// survey page loaded messages will be added and tested automatically
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video popup closed'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// make sure review mode events are included
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'review mode exited'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// assert that page loads are considered to stop video playback
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// test two play events in a row
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// test play time
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 3,
					'playCount' => 1,
				]
			]
		);

		// test repeats after end
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
				['message' => 'video played', 'seconds' => 0],
				['message' => 'video ended'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// test repeats after seeking back to the beginning
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played', 'seconds' => 0],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// test repeats after seeking back near to the beginning
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played', 'seconds' => 1],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 2,
				]
			]
		);

		// make sure pausing and playing early in the video doesn't count as an extra play
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video paused'],
				['message' => 'video played'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				]
			]
		);

		// test repeats after going forward and backward a page
		$this->assertVideoStats(
			[
				['message' => 'video played'],

				// Add some message to put us past the threshold considered a new play.
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],
				['message' => 'foo'],

				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'video played', 'seconds' => 0],
				['message' => 'video paused'],
			],
			[
				'video_1' => [
					'playTime' => 7,
					'playCount' => 2,
				]
			]
		);

		// test multiple videos
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video ended'],
				['message' => 'video played', 'field' => 'video_2'],
				['message' => 'video ended', 'field' => 'video_2'],
			],
			[
				'video_1' => [
					'playTime' => 1,
					'playCount' => 1,
				],
				'video_2' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);

		// test multiple videos playing when page changes
		$this->assertVideoStats(
			[
				['message' => 'video played'],
				['message' => 'video played', 'field' => 'video_2'],
			],
			[
				'video_1' => [
					'playTime' => 2,
					'playCount' => 1,
				],
				'video_2' => [
					'playTime' => 1,
					'playCount' => 1,
				]
			]
		);
	}

	private function assertVideoStats($logs, $expectedStats, $wrapInPageLoadLogs = true)
	{
		$instrument = 'instrument1';

		if($wrapInPageLoadLogs){
			$pageLoadLog = ['message' => 'survey page loaded', 'instrument' => $instrument];
			array_unshift($logs, $pageLoadLog);
			array_push($logs, $pageLoadLog);
		}

		$logs = $this->flushOutMockLogs($logs);

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods,
			$videoStats
		) = $this->analyzeLogEntries($logs, $instrument);

		foreach($videoStats as &$stats){
			// Remove unused temporary stats that we don't want to compare.
			unset($stats['currentPlayLog']);
			unset($stats['lastPositionInSeconds']);
		}

		if($expectedStats !== $videoStats){
			$this->dump($expectedStats, 'expected stats');
			$this->dump($videoStats, 'actual stats');
			throw new Exception("Actual video stats did not match expected");
		}
	}

	private function testGetSessionsFromLogs(){
		$assert = function($logs){
			$expectedSessions = [];
			for($i=0; $i<count($logs); $i++){
				$log = &$logs[$i];
				$log['log_id'] = $i;

				$sessionIndex = @$log['session'];
				if($sessionIndex === null){
					continue; // don't include this log in a session
				}

				$instrument = @$log['instrument'];
				if(@$log['copy-previous-instrument']){
					$instrument = $expectedSessions[$sessionIndex-1]['instrument'];
				}

				$session = &$expectedSessions[$sessionIndex];
				if(!$session){
					$session = [
						'timestamp' => $log['timestamp'],
						'record' => $log['record'],
						'instrument' => $instrument
					];

					$expectedSessions[$sessionIndex] = &$session;
				}

				// This value is only used to generate the expected sessions for testing.
				// It should not be included in the log data passed to getSessionsFromLogs() since sessions should be determined independently (the point of this test).
				unset($log['session']);

				$session['logs'][] = $log;
				if($instrument){
					$session['lastInstrument'] = $instrument;
				}
			}

			$results = new MockMySQLResult($logs);
			$actualSessions = $this->getSessionsFromLogs($results);

			$this->assertSame($expectedSessions, $actualSessions);
		};

		// two basic sessions
		$assert(
			[
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1], // simulate a message without an instrument set
				['session' => 1, 'record' => 1, 'instrument' => 'b'],
			]
		);

		// two overlapped records
		$assert(
			[
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
				['session' => 1, 'record' => 2, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
				['session' => 1, 'record' => 2],
			]
		);

		// sessions split by a timeout
		$time1 = 1;
		$time2 = $time1 + OddcastAvatarExternalModule::SESSION_TIMEOUT -1;
		$time3 = $time2 + OddcastAvatarExternalModule::SESSION_TIMEOUT;
		$assert(
			[
				['session' => 0, 'record' => 1, 'timestamp' => $time1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1, 'timestamp' => $time2],
				['session' => 1, 'record' => 1, 'timestamp' => $time3, 'copy-previous-instrument' => true],
			]
		);

		// logs start in the middle of a session
		// The first session log must have an instrument set for analyzeLogEntries() to work properly.
		$assert(
			[
				[], // simulate a record from the middle of a session that doesn't have an instrument
				['session' => 0, 'record' => 1, 'instrument' => 'a'],
				['session' => 0, 'record' => 1],
			]
		);
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