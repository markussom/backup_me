<?php
namespace Markussom\BackupMe\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Markus Sommer <markussom@me.com>, CreativeWorkspace
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Markussom\BackupMe\Utility\BackupUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class BackupController
 *
 * @author Markus Sommer
 * @package Markussom\BackupMe\Controller
 */
class BackupController extends ActionController {

	/**
	 * Action list
	 *
	 * @return void
	 */
	public function indexAction() {
		$getBackupedDirs = GeneralUtility::get_dirs(PATH_site . 'backup/files/');
		if (is_array($getBackupedDirs)) {
			foreach ($getBackupedDirs as $getBackupedDir) {
				$fileBackups[$getBackupedDir] = self::getBackupsByFile('backup/files/' . $getBackupedDir . '/backup.log');
			}
		}
		if (!empty($fileBackups)) {
			$this->view->assign('fileBackups', $fileBackups);
		}
		$databaseBackups = self::getBackupsByFile('backup/database/backup.log');
		if (!empty($databaseBackups)) {
			$this->view->assign('databaseBackups', $databaseBackups);
		}
		$this->view->assign('pathSite', PATH_site);
	}

	/**
	 * Database Backup Action
	 *
	 * @return void
	 */
	public function dbBackupAction() {
		$path = BackupUtility::backupTable(PATH_site . 'backup/database/');
		$this->addFlashMessage('Datei geschieben ' . $path, 'Backup vollständig');
		$this->forward('index');
	}

	/**
	 * Remove empty values from array Recursively
	 *
	 * @param array $array The givin array
	 *
	 * @return void
	 */
	static public function removeEmptyValuesRecursively(array &$array = array()) {
		foreach ($array as $index => $value) {
			if (empty($value)) {
				unset($array[$index]);
			} elseif (is_array($value)) {
				self::removeEmptyValuesRecursively($value);
			}
		}
	}

	/**
	 * Get the files from Log
	 *
	 * @param string $logfile The log file
	 *
	 * @return array|string
	 */
	protected function getBackupsByFile($logfile) {
		$backupFile = PATH_site . $logfile;
		if (file_exists($backupFile)) {
			$backupArray = array();
			$backupsLog = explode(PHP_EOL, file_get_contents($backupFile));
			foreach ($backupsLog as $backupsLogLine) {
				$backupArray[] = unserialize($backupsLogLine);
			}
			self::removeEmptyValuesRecursively($backupArray);
			return $backupArray;
		}
		return '';
	}
}