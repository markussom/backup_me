<?php
/**
 * Created by PhpStorm.
 * User: markussommer
 * Date: 16.07.15 | 29
 * Time: 17:01
 */

namespace Markussom\BackupMe\Command;

use Markussom\BackupMe\Utility\BackupUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class FileBackupCommandController
 *
 * @package Markussom\BackupMe\Command
 * @author Markus Sommer
 */
class BackupCommandController extends CommandController {

	/**
	 * @var string
	 */
	protected $backupPath = '';

	/**
	 * @var int
	 */
	protected $backupsToKeep = 3;

	/**
	 * Backup all files and folders in the given folder
	 *
	 * @param string $folder Folder to backup
	 * @param int $backupsToKeep Backups to keep
	 */
	public function filesBackupCommand($folder, $backupsToKeep = 3) {
		/** @var string backupPath set backup path */
		$this->backupPath = PATH_site . 'backup/files/' . $folder . '/';
		$this->backupsToKeep = $backupsToKeep;

		self::backupFolder($folder);
		BackupUtility::removeOldBackups($this->backupPath, $this->backupsToKeep);
	}

	/**
	 * @param int $backupsToKeep
	 */
	public function dbBackupCommand($backupsToKeep = 3) {
		/** @var string backupPath set backup path */
		$this->backupPath = PATH_site . 'backup/database/';
		$this->backupsToKeep = $backupsToKeep;

		BackupUtility::backupTable($this->backupPath);
		BackupUtility::removeOldBackups($this->backupPath, $this->backupsToKeep);
	}

	/**
	 * @param string $folder
	 *
	 * @return bool
	 */
	protected function backupFolder($folder) {
		GeneralUtility::mkdir_deep($this->backupPath);
		$compressedFileName = $folder . '_' . time() . '.tar';
		$phar = new \PharData($this->backupPath . $compressedFileName);
		if ($phar->buildFromDirectory(PATH_site . $folder, '/^(?!_temp_|_processed_|_recycler_).*/')) {
			BackupUtility::generateBackupLog($folder, $compressedFileName . '.gz', $this->backupPath, $this->backupsToKeep);
			BackupUtility::gzCompressFile($this->backupPath . $compressedFileName);
			return TRUE;
		}
		return FALSE;
	}
}