<?php
defined('TYPO3_MODE') or die('hard');

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'Markussom.' . $_EXTKEY,
		'tools',
		'mod1',
		'',
		array(
			'Backup' => 'index',
		),
		array(
			'access' => 'user,group',
			'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod1.xlf',
		)
	);
}