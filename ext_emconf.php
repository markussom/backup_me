<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "backup_me"
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Backup Me',
    'description' => 'Backup your files and database',
    'category' => 'misc',
    'author' => 'Markus Sommer',
    'author_email' => 'markussom@me.com',
    'state' => 'alpha',
    'internal' => '1',
    'version' => '0.0.1',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.0-7.4.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
