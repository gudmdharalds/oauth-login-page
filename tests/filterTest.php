<?php

require_once(__DIR__ . "/../config.php");
require_once(__DIR__ . "/../includes.php");
require_once(__DIR__ . "/shared.php");


class FilterTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		global $lp_config;

		PHPUnit_Framework_Error_Notice::$enabled = TRUE;

		$lp_config = __lp__unittesting_lp_config_fake();

		// Save snapshot
		__lp__unittesting_superglobals_snapshot(TRUE);
	}

	public function tearDown() {
		global $lp_config;

		__lp_unittesting_lp_config_cleanups();

		unset($lp_config);

		// Put snapshot in place
		__lp__unittesting_superglobals_snapshot(FALSE);
	}

	public function test_filter_register_callback_fail1() {
		global $lp_filters;

		$lp_filters_pre = $lp_filters;

		$this->assertFalse(	
			lp_filter_register_callback(
				'test_filter',
				'lp_somenonexistingfunc_LLLLIIIUUU'
			)
		);

		$this->assertEquals(
			$lp_filters_pre,
			$lp_filters
		);
	}

	public function test_filter_register_callback_fail2() {
		global $lp_filters;

		$lp_filters_pre = $lp_filters;

		function filter_func() {
		}

		$this->assertTrue(
			lp_filter_register_callback(
				'test_filter',
				'filter_func'
			)
		);

		$lp_filters_pre['test_filter'] = array(
			0 => 'filter_func'
		);

		$this->assertEquals(
			$lp_filters_pre,
			$lp_filters
		);


		$this->assertFalse(
			lp_filter_register_callback(
				'test_filter',
				'somenonexistingfunc'
			)
		);

		$this->assertEquals(
			$lp_filters_pre,
			$lp_filters
		);
	}


	/**
	 * @depends test_filter_register_callback_fail2
	 */
       
	public function test_filter_register_callback_success1() {
		global $lp_filters;

		$lp_filters_pre = $lp_filters;

		function filter_func2() {
		}		

		$this->assertTrue(
			lp_filter_register_callback(
				'test_filter',
				'filter_func2'
			)
		);

		$lp_filters_pre['test_filter'] = array(
			0 => 'filter_func2'
		);

		$this->assertEquals(
			$lp_filters_pre,
			$lp_filters
		);
	}

	/**
	 * @depends test_filter_register_callback_success1
	 */

	public function test_filter_apply() {
		function filter_func3($data1, $data2) {
			$data1 .= 'b';
			$data2 .= 'd';

			return array($data1, $data2);
		}

		$this->assertTrue(
			lp_filter_register_callback(
				'test_filter2',
				'filter_func3'
			)
		);

		/*
		 * Lets apply test_filter2 to on data
		 */

		$ret_filter_apply = lp_filter_apply(
			'test_filter2', 
			array(
				'a', 
				'c'
			)
		);

		$this->assertNotEquals(
			$ret_filter_apply,
			FALSE
		);

		list($r_1, $r_2) = $ret_filter_apply;

		$this->assertEquals('ab', $r_1);
		$this->assertEquals('cd', $r_2);


		/*
		 * Lets apply test_filter2 on resulting data
		 */

		$ret_filter_apply = lp_filter_apply(
			'test_filter2', 
			array(
				$r_1,
				$r_2
			)
		);


		$this->assertNotEquals(
			$ret_filter_apply, 
			FALSE
		);

		list($r_1, $r_2) = $ret_filter_apply;

		$this->assertEquals('abb', $r_1);
		$this->assertEquals('cdd', $r_2);
	}
}

