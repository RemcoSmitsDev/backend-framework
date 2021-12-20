<?php

register_shutdown_function(function () {
	// no error
	if (!error_get_last()) {
		return false;
	}

	if (error_get_last()['type'] === E_ERROR) {
		// send request to ray
		ray(error_get_last())->type('error')->color('red');
	} else {
		// send request to ray
		ray(error_get_last())->type('error')->color('orange');
	}
});
