<?php
namespace Markussom\BackupMe\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackupUtility
 *
 * @author Markus Sommer
 */
class BackupUtility
{

    /**
     * Exclude these tables from the database dump
     *
     * @var array
     */
    protected static $excludeTableNames = array('sys_log', 'sys_history');

    /**
     * Exclude any table matching this pattern from the database dump
     *
     * @var string
     */
    protected static $excludeTablePattern = '/^(cf_|cache).*/';

    /**
     * TODO: replace the $link stuff by usage of $GLOBALS['TYPO3_DB']
     *
     * @param string $outputFolder
     * @return string
     */
    public static function backupTable($outputFolder)
    {
        GeneralUtility::mkdir_deep($outputFolder);
        $host = $GLOBALS['TYPO3_CONF_VARS']['DB']['host'];
        $user = $GLOBALS['TYPO3_CONF_VARS']['DB']['username'];
        $pass = $GLOBALS['TYPO3_CONF_VARS']['DB']['password'];
        $name = $GLOBALS['TYPO3_CONF_VARS']['DB']['database'];
        $link = mysqli_connect($host, $user, $pass);
        mysqli_select_db($link, $name);
        $listDbTables = array_column(mysqli_fetch_all($link->query('SHOW TABLES')), 0);
        $listDbTables = self::removeCacheAndLogTables($listDbTables);
        $return = '';
        foreach ($listDbTables as $table) {
            $result = mysqli_query($link, 'SELECT * FROM ' . $table);
            $numFields = mysqli_num_fields($result);

            $return .= 'DROP TABLE ' . $table . ';';
            $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . $row2[1] . ";\n\n";

            for ($i = 0; $i < $numFields; $i++) {
                while (($row = mysqli_fetch_row($result))) {
                    $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $numFields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if (isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        } else {
                            $return .= '""';
                        }
                        if ($j < ($numFields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }

        //save file
        $backupFileName = 'db-backup_' . time() . '.sql';
        $handle = fopen($outputFolder . $backupFileName, 'w+');
        fwrite($handle, $return);
        fclose($handle);
        self::generateBackupLog($name, $backupFileName . '.gz', $outputFolder, 3);
        return self::gzCompressFile($outputFolder . 'db-backup_' . time() . '.sql');
    }

    /**
     * @param array $listDbTables
     * @return array
     */
    protected static function removeCacheAndLogTables(array $listDbTables = array())
    {
        foreach ($listDbTables as $index => $listDbTable) {
            if (preg_match(self::$excludeTablePattern, $listDbTable)) {
                unset($listDbTables[$index]);
            } elseif (in_array($listDbTable, self::$excludeTableNames)) {
                unset($listDbTables[$index]);
            }
        }
        return $listDbTables;
    }

    /**
     * @param string $folder
     * @param string $backupFileName
     * @param string $backupPath
     * @param int $backupsToKeep
     * @return bool
     */
    public static function generateBackupLog($folder, $backupFileName, $backupPath, $backupsToKeep)
    {
        $fileContent = '';
        $content = serialize(
            array(
                'date' => time(),
                'folder' => $folder,
                'filename' => $backupFileName,
            )
        );

        $file = $backupPath . 'backup.log';
        if (file_exists($file)) {
            $current = explode(PHP_EOL, file_get_contents($file));
            $current = self::removeEmptyValuesRecursively($current);
            while (count($current) > $backupsToKeep - 1) {
                reset($current);
                unset($current[key($current)]);
            }
            $current[count($current) + 1] = $content;
            unlink($file);
            foreach ($current as $currentLine) {
                $fileContent .= $currentLine . PHP_EOL;
            }
        } else {
            $fileContent = $content;
        }
        file_put_contents($file, $fileContent);
        return true;
    }

    /**
     * @param array $array
     * @return array
     */
    static public function removeEmptyValuesRecursively(array $array = array())
    {
        foreach ($array as $index => $value) {
            if (empty($value)) {
                unset($array[$index]);
            } elseif (is_array($value)) {
                $array = self::removeEmptyValuesRecursively($value);
            }
        }
        return $array;
    }

    /**
     * GZIPs a file on disk (appending .gz to the name)
     *
     * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
     * Based on function by Kioob at:
     * http://www.php.net/manual/en/function.gzwrite.php#34955
     *
     * @param string $source Path to file that should be compressed
     * @param integer $level GZIP compression level (default: 9)
     * @return string New filename (with .gz appended) if success, or false if operation fails
     */
    public static function gzCompressFile($source, $level = 9)
    {
        $destination = $source . '.gz';
        $mode = 'wb' . $level;
        $error = false;
        if (($fpOut = gzopen($destination, $mode))) {
            if (($fpIn = fopen($source, 'rb'))) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 512));
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            gzclose($fpOut);
        } else {
            $error = true;
        }
        if ($error) {
            return false;
        }
        unlink($source);
        return $destination;
    }

    /**
     * Remove old Backups
     *
     * @param string $backupPath Path to the Backup
     * @param int $backupsToKeep Backups to keep
     *
     * @return bool return
     */
    public static function removeOldBackups($backupPath, $backupsToKeep)
    {
        $oldBackupFiles = GeneralUtility::getFilesInDir($backupPath, '', false, '', 'backup.log');
        while (count($oldBackupFiles) > $backupsToKeep) {
            unlink($backupPath . array_shift($oldBackupFiles));
        }
        return true;
    }
}
