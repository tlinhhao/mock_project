<?php

/**
 * Store the session on disk. The cookie driver refuses payloads over 4KB,
 * and a blog draft with article HTML exceeds that limit.
 */
return array(
	'driver' => 'file',
	'file' => array(
		'path' => APPPATH.'tmp/',
	),
);
