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
		$this->testAnalyzeLogEntries_avatar();
        $this->testAnalyzeLogEntries_video();
        $this->testGetSessionsFromLogs();
        $this->testSetAvatarAnalyticsFields();
		$this->testPageStats();
		$this->testGetAggregateStats_mixedInstruments();
		$this->testGetAggregateStats_mixedRecords();
		$this->testGetAggregateStats_partials();
		$this->testGetAggregateStats_videos();
		$this->testGetAggregateStats_popups();
	}

	private function testGetAggregateStats_mixedInstruments()
	{
		$logs = [
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'a', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'a', 'page' => 2],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'a' ],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'b', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'b', 'page' => 2],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'b' ],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'a', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'a', 'page' => 2],
			[],
			['message' => 'survey complete',   'record' => 2, 'instrument' => 'a' ],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'b', 'page' => 1],
			[],
			[],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'b', 'page' => 2],
			[],
			[],
			[],
			['message' => 'survey complete',   'record' => 2, 'instrument' => 'b' ],
		];

		$records = [1 => true, 2 => true];
		$expected = [
			'records' => $records,
			'instruments' => [
				'a' => [
					'records' => $records,
					'pages' => [
						1 => [
							'records' => $records,
							'seconds' => 2,
							'avatarSeconds' => 0,
						],
						2 => [
							'records' => $records,
							'seconds' => 3,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 5,
					'avatarSeconds' => 0,
				],
				'b' => [
					'records' => $records,
					'pages' => [
						1 => [
							'records' => $records,
							'seconds' => 4,
							'avatarSeconds' => 0,
						],
						2 => [
							'records' => $records,
							'seconds' => 5,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 9,
					'avatarSeconds' => 0,
				],
			]
		];
		
		$this->assertAggregateStats($logs, $expected);
	}

	private function testGetAggregateStats_mixedRecords(){
		$logs = [
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'a', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'a', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'a', 'page' => 2],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'a', 'page' => 2],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'a' ],
			[],
			['message' => 'survey complete',   'record' => 2, 'instrument' => 'a' ],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'b', 'page' => 1],
			[],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'b', 'page' => 1],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'b', 'page' => 2],
			[],
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'b', 'page' => 2],
			[],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'b' ],
			['message' => 'survey complete',   'record' => 2, 'instrument' => 'b' ],
		];

		$records = [1 => true, 2 => true];
		$expected = [
			'records' => $records,
			'instruments' => [
				'a' => [
					'records' => $records,
					'pages' => [
						1 => [
							'records' => $records,
							'seconds' => 4,
							'avatarSeconds' => 0,
						],
						2 => [
							'records' => $records,
							'seconds' => 5,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 9,
					'avatarSeconds' => 0,
				],
				'b' => [
					'records' => $records,
					'pages' => [
						1 => [
							'records' => $records,
							'seconds' => 6,
							'avatarSeconds' => 0,
						],
						2 => [
							'records' => $records,
							'seconds' => 7,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 13,
					'avatarSeconds' => 0,
				],
			]
		];

		$this->assertAggregateStats($logs, $expected);
	}

	private function testGetAggregateStats_partials(){
		$logs = [
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'a', 'page' => 1],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'a' ],
			['message' => 'survey page loaded', 'record' => 1, 'instrument' => 'b', 'page' => 1],
			[],
			[],
			['message' => 'survey complete',   'record' => 1, 'instrument' => 'b' ],
			// In most cases a report would not contain page 2 without containing page 1,
			// but this is still a a good test to make sure logs are being analyzed properly.
			['message' => 'survey page loaded', 'record' => 2, 'instrument' => 'a', 'page' => 2],
			[],
			['message' => 'survey complete',   'record' => 2, 'instrument' => 'a' ],
		];

		$expected = [
			'records' => [1 => true, 2 => true],
			'instruments' => [
				'a' => [
					'records' => [1 => true, 2 => true],
					'pages' => [
						1 => [
							'records' => [1 => true],
							'seconds' => 1,
							'avatarSeconds' => 0,
						],
						2 => [
							'records' => [2 => true],
							'seconds' => 2,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 3,
					'avatarSeconds' => 0,
				],
				'b' => [
					'records' => [1 => true],
					'pages' => [
						1 => [
							'records' => [1 => true],
							'seconds' => 3,
							'avatarSeconds' => 0,
						],
					],
					'seconds' => 3,
					'avatarSeconds' => 0,
				],
			]
		];

		$this->assertAggregateStats($logs, $expected);
	}

	private function testGetAggregateStats_videos(){
		$logs = [
			['message' => 'survey page loaded', 'record' => 1,  'instrument' => 'a'],
			['message' => 'video played', 'record' => 1, 'field' => 'video_1', 'seconds' => 0],
			['message' => 'video paused', 'record' => 1, 'field' => 'video_1', 'seconds' => 1],
			['message' => 'video played', 'record' => 1, 'field' => 'video_2', 'seconds' => 1],
			['message' => 'video paused', 'record' => 1, 'field' => 'video_2', 'seconds' => 2],
			['message' => 'survey page loaded', 'record' => 2,  'instrument' => 'a'],
			['message' => 'video played', 'record' => 2, 'field' => 'video_2', 'seconds' => 0],
			[],
			[],
			[],
			[],
			[],
			[],
			['message' => 'video paused', 'record' => 2, 'field' => 'video_2', 'seconds' => 1],
			['message' => 'video played', 'record' => 2, 'field' => 'video_2', 'seconds' => 0],
			['message' => 'video paused', 'record' => 2, 'field' => 'video_2', 'seconds' => 1],
		];

		$expected = [
			'videos' => [
				'video_1' => [
					'records' => [1 => true],
					'playTime' => 1,
					'playCount' => 1,
				],
				'video_2' => [
					'records' => [1 => true, 2 => true],
					'playTime' => 9,
					'playCount' => 3,
				]
			]
		];

		$this->assertAggregateStats($logs, $expected);
	}

	private function testGetAggregateStats_popups(){
		$logs = [
			['message' => 'survey page loaded', 'record' => 1,  'instrument' => 'a'],
			['message' => 'popup opened', 'record' => 1, 'link text' => 'one'],
			['message' => 'popup closed', 'record' => 1, 'link text' => 'one'],
			['message' => 'survey page loaded', 'record' => 2,  'instrument' => 'a'],
			['message' => 'popup opened', 'record' => 2, 'link text' => 'one'],
			['message' => 'popup closed', 'record' => 2, 'link text' => 'one'],
			['message' => 'popup opened', 'record' => 2, 'link text' => 'two'],
			['message' => 'popup closed', 'record' => 2, 'link text' => 'two'],
		];

		$expected = [
			'popups' => [
				'one' => [
					'records' => [1 => true, 2 => true],
					'viewCount' => 2,
				],
				'two' => [
					'records' => [2 => true],
					'viewCount' => 1,
				]
			]
		];

		$this->assertAggregateStats($logs, $expected);
	}

	private function assertAggregateStats($logs, $expected){
		$logs = $this->flushOutMockLogs($logs);
		$results = new MockMySQLResult($logs);
		list($actual, $errors) = $this->module->getAggregateStats($this->module->getSessionsFromLogs($results));
		
		if(!empty($errors)){
			$this->dump($errors);
			throw new Exception("A unit test failed because incomplete log data was provided.");
		}
		
		foreach(['instruments', 'records'] as $optionalKey){
			if(!isset($expected[$optionalKey])){
				// This unit test isn't testing this key, so remove it.
				unset($actual[$optionalKey]);
			}
		}
		
		$this->assertSame($expected, $actual);
	}

    private function testGetTimePeriodString()
	{
		$assert = function($expected, $seconds){
			$actual = $this->module->getTimePeriodString($seconds);
			if($expected !== $actual){
				throw new Exception("Expected '$expected' but got '$actual'!");
			}
		};

		$assert('0 seconds', 0);
		$assert('1 second', 1);
		$assert('2 seconds', 2);
		$assert('3 seconds', 10/3); // should round to nearest second
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
		) = $this->module->analyzeLogEntries($logs, $instrument);

		$this->assertSame(1, $lastSurveyLog['timestamp'] - $firstSurveyLog['timestamp']);
	}

	private function testAnalyzeLogEntries_avatar()
	{
		$showId = rand();
		$showId2 = rand();

		// basic usage
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			1
		);

		// basic usage without survey complete event
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			1	
		);

		// different character selections
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId2,
					'startIndex' => 2,
					'endIndex' => 3
				]
			],
			2
		);

		// repeated character selections
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId,
					'startIndex' => 2,
					'endIndex' => 3
				]
			],
			2
		);

		// disabling and enabling
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'avatar disabled'],
				['message' => 'avatar enabled'],
				['message' => 'survey complete'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				],
				[
					'show id' => $showId,
					'startIndex' => 2,
					'endIndex' => 3,
					'disabled' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 3,
					'endIndex' => 4
				]
			],
			2
		);

		// disabling to begin with
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
				['message' => 'avatar disabled'],
				['message' => 'avatar enabled'], // the selection dialog will be displayed again here
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'survey page loaded', 'instrument' => 'instrument1'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'startIndex' => 1,
					'endIndex' => 2,
					'disabled' => true
				],
				[
					'startIndex' => 2,
					'endIndex' => 3,
				],
				[
					'show id' => $showId,
					'startIndex' => 3,
					'endIndex' => 4,
				]
			],
			1
		);

		// second of two instruments, and avatar left enabled from first
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'this message creates a gap between the surrounding logs so that the following event is not assumed to be the initial character selection dialog'],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'survey complete'],
			],
			[
				[
					'show id' => $showId,
					'startIndex' => 0,
					'endIndex' => 2
				],
				[
					'show id' => $showId2,
					'startIndex' => 2,
					'endIndex' => 3
				]
			],
			3,
			[
				['message' => 'character selected', 'show id' => $showId]
			]
		);

		// second of two instruments, and avatar left disabled from first
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'avatar enabled'],
				['message' => 'survey complete'],
			],
			[
				[
					'show id' => $showId,
					'startIndex' => 0,
					'endIndex' => 1,
					'disabled' => true
				],
				[
					'show id' => $showId,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			1,
			[
				['message' => 'character selected', 'show id' => $showId],
				['message' => 'avatar disabled'],
			]
		);

		// second of two instruments opened at different times via direct links from participant list
		// avatar was left open on first instrument, but that avatar period should not be included on the second instrument
		$this->assertAvatarUsagePeriods(
			[
				['message' => 'survey page loaded', 'instrument' => 'instrument2'],
				['message' => 'character selected', 'show id' => $showId2],
				['message' => 'some action to simulate the user staying on the survey after selecting the character'],
			],
			[
				[
					'startIndex' => 0,
					'endIndex' => 1,
					'initialSelectionDialog' => true
				],
				[
					'show id' => $showId2,
					'startIndex' => 1,
					'endIndex' => 2
				]
			],
			2,
			[
				['message' => 'character selected', 'show id' => $showId],
			]
		);
	}

	private function assertAvatarUsagePeriods($logs, $expectedPeriods, $expectedAvatarSeconds, $spoofedPrecedingAvatarLogs = null)
	{
		$this->module->spoofedPrecedingAvatarLogs = $spoofedPrecedingAvatarLogs = $this->flushOutMockLogs($spoofedPrecedingAvatarLogs);
		$logs = $this->flushOutMockLogs($logs, end($spoofedPrecedingAvatarLogs));

		list(
			$firstReviewModeLog,
			$firstSurveyLog,
			$lastSurveyLog,
			$avatarUsagePeriods,
			$videoStats,
			$popupStats,
			$pageStats
		) = $this->module->analyzeLogEntries($logs, $logs[0]['instrument']);

		$this->assertSame($expectedAvatarSeconds, $pageStats['1']['avatarSeconds']);

		if(count($avatarUsagePeriods) !== count($expectedPeriods)){
			$this->dump($expectedPeriods, '$expected');
			$this->dump($avatarUsagePeriods, '$actual');
			throw new Exception("Expected " . count($expectedPeriods) . " usage period(s), but found " . count($avatarUsagePeriods));
		}

		// Used to specifically order keys such that the triple equals check works as expected below.
		$moveKeyToEnd = function(&$array, $key){
			if(isset($array[$key])){
				$value = $array[$key];
			}
			else{
				// Make values default to false
				$value = false;
			}

			unset($array[$key]);
			$array[$key] = $value;
		};

		for($i=0; $i<count($expectedPeriods); $i++){
			$expected = $expectedPeriods[$i];
			$actual = $avatarUsagePeriods[$i];

			if(!isset($expected['show id'])){
				$expected['show id'] = null;
			}

			$moveKeyToEnd($expected, 'initialSelectionDialog');

			$startIndex = $expected['startIndex'];
			$endIndex = $expected['endIndex'];

			$expected['start'] = $logs[$startIndex]['timestamp'];

			$moveKeyToEnd($expected, 'disabled');

			$expected['end'] = $logs[$endIndex]['timestamp'];

			unset($expected['startIndex']);
			unset($expected['endIndex']);

			if($expected['start'] >= $expected['end']){
				$this->dump($expected, '$expected');
				throw new Exception("The expected start is not before the expected end for index $i");
			}

			if($expected !== $actual){
				$this->dump($expected, '$expected');
				$this->dump($actual, '$actual');
				throw new Exception("The expected and actual periods did not match!");
			}
		}

		$this->module->spoofedPrecedingAvatarLogs = null;
	}

	private function flushOutMockLogs($logs, $lastLog = null)
	{
		if($logs === null){
			return null;
		}

		if($lastLog){
			$lastId = $lastLog['log_id'];
			$lastTimestamp = $lastLog['timestamp'];
		}
		else{
			$lastId = 0;
			$lastTimestamp = time();
		}

		$nextId = $lastId+1;

		$createLog = function($params) use (&$nextId, &$lastTimestamp){
			$message = $params['message'];
			
			if($message === 'survey page loaded' && !isset($params['page'])){
				$params['page'] = 1;
			}

			$isVideoMessage = strpos($message, 'video ') === 0;
			if($isVideoMessage){
				if(!isset($params['seconds'])){
					$params['seconds'] = $nextId - 1;
				}

				if(!isset($params['field'])){
					$params['field'] = 'video_1';
				}
			}

			$params['log_id'] = $nextId;
			$nextId++;

			$params['timestamp'] = $lastTimestamp;
			$lastTimestamp++;

			if(isset($params['page'])){
				// Values are stored in the database as strings, so this makes tests more valid.
				$params['page'] = (string) $params['page'];
			}

			return $params;
		};

		for($i=0; $i<count($logs); $i++){
			$logs[$i] = $createLog($logs[$i]);
		}

		return $logs;
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
		) = $this->module->analyzeLogEntries($logs, $instrument);

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
			$actualSessions = $this->module->getSessionsFromLogs($results);

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

	private function testSetAvatarAnalyticsFields(){
		$assert = function($periods, $expectedData){
			if(!isset($expectedData['avatar_disabled'])){
				$expectedData = ['avatar_disabled' => 0] + $expectedData;
			}

            $actualData = [];
            $this->module->setAvatarAnalyticsFields($periods, $actualData);
			$this->assertSame($expectedData, $actualData);
		};

		// basic
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
			],
			[
				'avatar_id_1' => 1,
				'avatar_seconds_1' => 1
			]
		);

		// assert multiple period times combined
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 2],
				['show id' => 1, 'start' => 1, 'end' => 2],
			],
			[
				'avatar_id_1' => 1,
				'avatar_seconds_1' => 2,
				'avatar_id_2' => 2,
				'avatar_seconds_2' => 1
			]
		);

		// assert disabled works
		$assert(
			[
				['disabled' => true],
			],
			[
				'avatar_disabled' => 1
			]
		);

		// assert avatars ordered by most used
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 3],
			],
			[
				'avatar_id_1' => 2,
				'avatar_seconds_1' => 2,
				'avatar_id_2' => 1,
				'avatar_seconds_2' => 1
			]
		);

		// assert other works as expected
		$assert(
			[
				['show id' => 1, 'start' => 1, 'end' => 2],
				['show id' => 2, 'start' => 1, 'end' => 2],
				['show id' => 3, 'start' => 1, 'end' => 2],
				['show id' => 4, 'start' => 1, 'end' => 3],
				['show id' => 5, 'start' => 1, 'end' => 4],
				['show id' => 6, 'start' => 1, 'end' => 5],
			],
			[
				'avatar_id_1' => 6,
				'avatar_seconds_1' => 4,
				'avatar_id_2' => 5,
				'avatar_seconds_2' => 3,
				'avatar_id_3' => 4,
				'avatar_seconds_3' => 2,
				'avatar_seconds_other' => 3,
			]
		);

		// assert initialSelectionDialog skipped
		$assert(
			[
				['initialSelectionDialog' => true],
			],
			[]
		);

		// assert subsequent selection dialogs are skipped
		$assert(
			[
				[
					'initialSelectionDialog' => false,
					'disabled' => false,
					'show id' => null
				]
			],
			[]
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
        ) = $this->module->analyzeLogEntries($logs, $instrument);

		$this->assertSame([
            '1' => ['seconds' => 3],
            '2' => ['seconds' => 1]
        ], $pageStats);
    }

    private function assertSame($expected, $actual){
		if($expected !== $actual){
			?>
			<p>A unit test did not return an expected value.  Below is a the actual output from the test with any incorrect values crossed out and replaced with their expected values (underlined):</p>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/jsdiff/4.0.1/diff.min.js"></script>
			<pre id='a' style='display: none'><?=json_encode($expected, JSON_PRETTY_PRINT)?></pre>
			<pre id='b' style='display: none'><?=json_encode($actual, JSON_PRETTY_PRINT)?></pre>
			<pre id='result'></pre>
			<script>
				// This JS block was copied from: http://incaseofstairs.com/jsdiff/

				var a = document.getElementById('a');
				var b = document.getElementById('b');
				var result = document.getElementById('result');

				var diff = Diff.diffChars(a.textContent, b.textContent);
				var fragment = document.createDocumentFragment();
				for (var i=0; i < diff.length; i++) {

					if (diff[i].added && diff[i + 1] && diff[i + 1].removed) {
						var swap = diff[i];
						diff[i] = diff[i + 1];
						diff[i + 1] = swap;
					}

					var node;
					if (diff[i].removed) {
						node = document.createElement('del');
						node.appendChild(document.createTextNode(diff[i].value));
					} else if (diff[i].added) {
						node = document.createElement('ins');
						node.appendChild(document.createTextNode(diff[i].value));
					} else {
						node = document.createTextNode(diff[i].value);
					}
					fragment.appendChild(node);
				}

				result.textContent = '';
				result.appendChild(fragment);				
			</script>
			<?php

			throw new Exception("The expected and actual values are not the same (or not the same type).");
		}
	}

	private function dump($o, $label = null){
		$this->module->dump($o, $label);
	}
}