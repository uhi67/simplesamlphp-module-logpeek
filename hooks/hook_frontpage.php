<?php

declare(strict_types=1);

use SimpleSAML\Module;

/**
 * Hook to add the logpeek module to the frontpage.
 *
 * @param array &$links The links on the frontpage, split into sections.
 */
function logpeek_hook_frontpage(array &$links): void
{
    assert('is_array($links)');
    assert('array_key_exists("links", $links)');

    $links['config'][] = [
        'href' => class_exists('SimpleSAML_Module', false) ? SimpleSAML_Module::getModuleURL(
            'logpeek/'
        ) : Module::getModuleURL('logpeek/'),
        'text' => ['en' => 'SimpleSAMLphp logs access (Log peek)', 'no' => 'Vis simpleSAMLphp log'],
    ];
}
