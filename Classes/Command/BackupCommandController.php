<?php
namespace Markussom\BackupMe\Command;

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
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * TODO: inject BackupUtility and use non-static methods
 * Class FileBackupCommandController
 *
 * @author Markus Sommer
 */
class BackupCommandController extends CommandController
{

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
     * @return void
     */
    public function filesBackupCommand($folder, $backupsToKeep = 3)
    {
        $this->backupPath = PATH_site . 'backup/files/' . $folder . '/';
        $this->backupsToKeep = $backupsToKeep;

        self::backupFolder($folder);
        BackupUtility::removeOldBackups($this->backupPath, $this->backupsToKeep);
    }

    /**
     * @param int $backupsToKeep
     * @return void
     */
    public function dbBackupCommand($backupsToKeep = 3)
    {
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
    protected function backupFolder($folder)
    {
        GeneralUtility::mkdir_deep($this->backupPath);
        $compressedFileName = $folder . '_' . time() . '.tar';
        $phar = new \PharData($this->backupPath . $compressedFileName);
        if ($phar->buildFromDirectory(PATH_site . $folder, '/^(?!_temp_|_processed_|_recycler_).*/')) {
            BackupUtility::generateBackupLog($folder, $compressedFileName . '.gz', $this->backupPath, $this->backupsToKeep);
            BackupUtility::gzCompressFile($this->backupPath . $compressedFileName);
            return true;
        }
        return false;
    }
}
