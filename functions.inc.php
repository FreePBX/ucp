<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2013 Schmooze Com, INC
 * Copyright (C) 2013 Andrew Nagy <andrew.nagy@schmoozecom.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   FreePBX UCP BMO
 * @author   Andrew Nagy <andrew.nagy@schmoozecom.com>
 * @license   AGPL v3
 */
function ucp_hook_userman() {
	if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'showuser') {
		switch($_REQUEST['action']) {
			case 'showuser':
				$ucp = FreePBX::create()->Ucp;
				$user = $ucp->getUserByID($_REQUEST['user']);
				if(isset($_POST['submit'])) {
					$ucp->processModuleConfigPages($user);
					$ucp->expireUserSessions($_REQUEST['user']);
				}
				if(!empty($_REQUEST['deletesession'])) {
					$ucp->expireUserSession($_REQUEST['deletesession']);
					$ucp->setUsermanMessage(_('Deleted User Session'),'success');
				}
				return load_view(dirname(__FILE__).'/views/users_hook.php',array("mHtml" => $ucp->constructModuleConfigPages($user), "user" => $user, "sessions" => $ucp->getUserSessions($user['id'])));
			break;
			case 'deluser':
			break;
			default:
			break;
		}
	}
}