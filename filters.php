<?php


$lp_filters = array();

/*
 * LP_FILTER_REGISTER_CALLBACK:
 * 
 * Register a simple filter-function callback
 * for a specific filter. The filter-function
 * must be callable.
 *
 * Returns TRUE on success, FALSE on error.
 */

function lp_filter_register_callback($filter_name, $callback_function_name) {
	global $lp_filters;

	/* 
	 * Does the callback function exist?
	 */
	if (function_exists($callback_function_name) === FALSE) {
		lp_login_form(
			"Cannot call filter-function callback: " . $callback_function_name
		);
	}


	/*
	 * Register filter-function callback
	 */

	if (isset($lp_filters[$filter_name]) === FALSE) {
		$lp_filters[$filter_name] = array();
	}

	$lp_filters[$filter_name][] = $callback_function_name;

	return TRUE;
}


/*
 * LP_FILTER_APPLY:
 *
 * Will call all registered filter-function 
 * callbacks for the filter specified by $filter_name,
 * with items in $data_input array as arguments.
 *
 * Returns data returned from filter-function callbacks.
 * If any of the callbacks return FALSE, this function
 * will halt execution.
 */

function lp_filter_apply($filter_name, $data_input) {
	global $lp_filters;	

	foreach ($lp_filters[$filter_name] as 
		$callback_function_name) {

		/*
		 * Call callback function with
		 * $data_input as arguments. Note
		 * that $data_input might be altered
		 * by the function, and other functions --
		 * it might this change on run-time.
		 */
		$tmp_callback_ret = call_user_func_array(
			$callback_function_name,
			$data_input
		);


		/*
		 * Some error? Fail, if so.
		 */

		if ($tmp_callback_ret === FALSE) {
			lp_login_form(
				"Failure to communicate; pre-callback failed"
			);
		}

		$data_input = $tmp_callback_ret;
	}

	return $data_input;
}


