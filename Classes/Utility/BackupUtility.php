<?php
/**
 * Created by PhpStorm.
 * User: markussommer
 * Date: 28.07.15 | 31
 * Time: 13:36
 */

namespace Markussom\BackupMe\Utility;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class BackupUtility {

	public static function backupTable($outputFolder) {
		GeneralUtility::mkdir_deep($outputFolder);
		$host = $GLOBALS['TYPO3_CONF_VARS']['DB']['host'];
		$user = $GLOBALS['TYPO3_CONF_VARS']['DB']['username'];
		$pass = $GLOBALS['TYPO3_CONF_VARS']['DB']['password'];
		$name = $GLOBALS['TYPO3_CONF_VARS']['DB']['database'];
		$link = mysqli_connect($host, $user, $pass);
		mysqli_select_db($link, $name);
		$listdbtables = array_column(mysqli_fetch_all($link->query('SHOW TABLES')), 0);
		self::removeCacheAndLogTables($listdbtables);
		$return = '';
		foreach ($listdbtables as $table) {
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
	 * @param array $listdbtables
	 */
	private function removeCacheAndLogTables(array &$listdbtables = array()) {
		foreach ($listdbtables as $index => $listdbtable) {
			if (preg_match('/^(cf|cache).*/', $listdbtable)) {
				unset($listdbtables[$index]);
			}
			if (preg_match('/^(sys_log|sys_history).*/', $listdbtable)) {
				unset($listdbtables[$index]);
			}
		}

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
	public static function gzCompressFile($source, $level = 9) {
		$dest = $source . '.gz';
		$mode = 'wb' . $level;
		$error = FALSE;
		if (($fpOut = gzopen($dest, $mode))) {
			if (($fpIn = fopen($source, 'rb'))) {
				while (!feof($fpIn))
					gzwrite($fpOut, fread($fpIn, 1024 * 512));
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
		return $dest;
	}

	/**
	 * @param $folder
	 * @param $backupFileName
	 *
	 * @return bool
	 */
	public static function generateBackupLog($folder, $backupFileName, $backupPath, $backupsToKeep) {
		$fileContent = '';
		$content = serialize(
			array(
				'date' => time(),
				'folder' => $folder,
				'filename' => $backupFileName
			)
		);

		$file = $backupPath . 'backup.log';
		if (file_exists($file)) {
			$current = explode(PHP_EOL, file_get_contents($file));
			self::removeEmptyValuesRecursively($current);
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
		return TRUE;
	}

	/**
	 * @param array $array
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
	 * Remove old Backups
	 *
	 * @param string $backupPath Path to the Backup
	 * @param int $backupsToKeep Backups to keep
	 *
	 * @return bool return
	 */
	public static function removeOldBackups($backupPath, $backupsToKeep) {
		$oldBackupFiles = GeneralUtility::getFilesInDir($backupPath, '', FALSE, '', 'backup.log');
		while (count($oldBackupFiles) > $backupsToKeep) {
			unlink($backupPath . array_shift($oldBackupFiles));
		}
		return TRUE;
	}
}