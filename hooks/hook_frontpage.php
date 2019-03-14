<?php
/**
 * Hook to add the logpeek module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function logpeek_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['config'][] = array(
		'href' => class_exists('SimpleSAML_Module', false) ? SimpleSAML_Module::getModuleURL('logpeek/') : \SimpleSAML\Module::getModuleURL('logpeek/'),
		'text' => array('en' => 'SimpleSAMLphp logs access (Log peek)', 'no' => 'Vis simpleSAMLphp log'),
	);

}
