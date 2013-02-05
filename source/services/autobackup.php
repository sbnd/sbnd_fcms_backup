<?php
/**
* SBND F&CMS - Framework & CMS for PHP developers
*
* Copyright (C) 1999 - 2013, SBND Technologies Ltd, Sofia, info@sbnd.net, http://sbnd.net
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author SBND Techologies Ltd <info@sbnd.net>
* @package backup
* @version 1.1
*/
/**
 * Backups creator. This script can work and in WEB and in SQI.
 * 
 * @author Evgeni Baldzhiyski
 * @version 0.1
 * @since 27.03.2012
 * @package cms.services
 */
$autobackup_file = 'autobackup.php';

if(!class_exists('BASIC')){
	include_once "../cms/install.php";
}
include_once "../conf/site_config.php";

if($interval = (int)CMS_SETTINGS::init()->get('backup')){
	$storage = BASIC_SQL::init()->backupEngine->option('storage');
	
	$create_new = true;
	if($data = @file(BASIC::init()->root().$storage."/.".$autobackup_file)){
		if((int)$data[1] > time() - ($interval * 60 * 60 * 24)){
			$create_new = false;
		}
		unset($data);
	}
	if($create_new){
		$id = BASIC_SQL::init()->backupEngine->backup();
		
		if($file = fopen(BASIC::init()->root().$storage."/.".$autobackup_file, 'w')){
			fwrite($file, "<?php die('access denied'); /*\n".($id-30));
			fclose($file);
		}
	}
}