<?php

/**
 *
 * @package Simple vQmod OpenCart install script
 * @author Jay Gilford - http://vqmod.com/
 * @copyright Jay Gilford 2022
 * @version 0.5
 * @access public
 *
 * @information
 * This file will perform all necessary file alterations for the
 * OpenCart index.php files both in the root directory and in the
 * Administration folder. Please note that if you have changed your
 * default folder name from admin to something else, you will need
 * to edit the admin/index.php in this file to install successfully
 *
 * @license
 * Permission is hereby granted, free of charge, to any person to
 * use, copy, modify, distribute, sublicense, and/or sell copies
 * of the Software, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software
 *
 * @warning
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESSED OR IMPLIED.
 *
 */

// CHANGE THIS IF YOU EDIT YOUR ADMIN FOLDER NAME


if (!empty($_POST['admin_name'])) {
	//$admin = 'admin';
	$admin = htmlspecialchars(trim($_POST['admin_name']));
	// Counters
	$changes = 0;
	$writes = 0;
	$files = [];

	// Load class required for installation
	$main_class = 'ugrsr.class.php';
	if (!file_exists($main_class)) {
		die('ERROR - UGRSR CLASS NOT FOUND - Please ensure you have uploaded the vQmod install files correctly');
	}

	require($main_class);

	$vqmod_path = dirname(__FILE__, 2) . '/';

	// Get the vqmod folder via the path
	$vqmod_folder = basename($vqmod_path);

	// OpenCart path
	$opencart_path = dirname(__FILE__, 3) . '/';

	// Verify path is correct
	if(empty($opencart_path)) {
		die('ERROR - COULD NOT DETERMINE OPENCART PATH CORRECTLY - ' . __DIR__);
	}

	$write_errors = array();
	if(!is_writable($opencart_path . 'index.php')) {
		$write_errors[] = 'index.php not writeable';
	}
	if(!is_writable($opencart_path . $admin . '/index.php')) {
		$write_errors[] = 'Administrator index.php not writeable';
	}
	if(!is_writable($opencart_path . $vqmod_folder . '/pathReplaces.php')) {
		$write_errors[] = 'vQmod pathReplaces.php not writeable';
	}

	if(!empty($write_errors)) {
		die(implode('<br />', $write_errors));
	}

	// Create new UGRSR class
	$u = new UGRSR($opencart_path);

	// remove the # before this to enable debugging info
	#$u->debug = true;

	// Set file searching to off
	$u->file_search = false;

	// Attempt upgrade if necessary. Otherwise, just continue with normal install
	$u->addFile('index.php');
	$u->addFile($admin . '/index.php');

	$u->addPattern('~\$vqmod->~', 'VQMod::');
	$u->addPattern('~\$vqmod = new VQMod\(\);~', 'VQMod::bootup();');

	$result = $u->run();

	if(($result['writes'] > 0) && file_exists('../mods.cache')) {
		unlink('../mods.cache');
	}

	$u->clearPatterns();
	$u->resetFileList();

	// Add catalog index files to include
	$u->addFile('index.php');

	// Pattern to add vqmod include
	$u->addPattern('~// Startup~', "// vQmod\nrequire_once('./" . $vqmod_folder . "/vqmod.php');\nVQMod::bootup();\n\n// VQMODDED Startup");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);

	$u->clearPatterns();
	$u->resetFileList();

	// Add Admin index file
	$u->addFile($admin . '/index.php');

	// Pattern to add vqmod include
	$u->addPattern('~// Startup~', "//vQmod\nrequire_once('../" . $vqmod_folder . "/vqmod.php');\nVQMod::bootup();\n\n// VQMODDED Startup");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);

	$u->addFile('index.php');

	// Pattern to run required files through vqmod
	$u->addPattern('/require_once\(DIR_SYSTEM \. \'([^\']+)\'\);/', "require_once(VQMod::modCheck(DIR_SYSTEM . '$1'));");

	// Get number of changes during run
	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);

	$u->clearPatterns();
	$u->resetFileList();

	// 2022 - Qphoria
	// pathReplaces install

	// Add vqmod/pathReplaces.php file
	$u->addFile($vqmod_folder . '/pathReplaces.php');

	// Pattern to add vqmod include
	$u->addPattern('~// START REPLACES //~', "// VQMODDED START REPLACES //\nif (defined('DIR_CATALOG')) { \$replaces[] = array('~^admin\b~', basename(DIR_APPLICATION)); }");

	$result = $u->run();
	$writes += $result['writes'];
	$changes += $result['changes'];
	$files = array_merge($files, $result['files']);
	$files = array_unique($files);

	if ($writes) {
		echo "The following files have been updated:<br/>";
		foreach($files as $file) {
			echo $file, '<br/>';
		}
	} else {
		die('VQMOD ALREADY INSTALLED!');
	}

	// output result to user
	die('VQMOD HAS BEEN INSTALLED ON YOUR SYSTEM!');
}

echo 'vQmod Installer for OpenCart<br/>';
echo '<form method="post">
	<span>Admin Folder Name:<input type="text" name="admin_name" value="admin" /></span>
	<input type="submit" value="Go" />
</form>';
