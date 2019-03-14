<?php
/*
 * Configuration for the module logpeek.
 */

$config = array (
	'logfile'	=> '/var/log/simplesamlphp.log',
	'lines'		=> 1500,
	// Read block size. 8192 is max, limited by fread.
	'blocksz'	=> 8192,
	'requireAdmin' => true, // default is true.
	'requireAuth' => false, // default is false. Specify auht source if you want to require login
	'requiredAttrs' => array(),    // default is empty. Required attribute values to use this module
	/* Example
	'requiredAttrs' => array(
		'eduPersonScopedAffiliation' => 'employee@pte.hu',
		'ou' => '~KA IIG~', // ~~ indicates regex match
	),
	 */
);
