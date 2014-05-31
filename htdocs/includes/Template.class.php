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
namespace UCP;
class Template extends UCP {
	function __construct($UCP) {
		$this->UCP = $UCP;
	}

	public function generatePagnation($total,$current,$link,$break=10) {
		$start = (ceil($current / $break) * $break) - ($break -1);
		$end = ceil($current / $break) * $break;
		$data = array(
			'startPage' => $start,
			'activePage' => $current,
			'endPage' => ($end < $total) ? $end : $total,
			'totalPages' => $total,
			'link' => $link
		);
		return $this->UCP->View->load_view(dirname(__DIR__).'/views/templates/pagnation.php', $data);
	}
}
