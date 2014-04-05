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
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Home extends Modules{
    protected $module = 'Home';

	function __construct($Modules) {
		$this->Modules = $Modules;
	}

	function getDisplay() {
        $modules = $this->Modules->getModulesByMethod('getHomeWidgets');
        $html = $this->loadLESS();
        $html .= '<div class="row">';
        foreach($modules as $module) {
            $widgets = $this->Modules->$module->getHomeWidgets();
            foreach($widgets as $data) {
                $html .= '<div class="col-sm-'.$data['size'].'">';
                $html .= '<div id="'.$module.'-widget" class="widget">';
                $html .= '<div id="'.$module.'-widget-title" class="widget-title">'.$data['title'].'<a onclick="Home.refresh(\''.$module.'\')"><i class="fa fa-refresh"></i></a></div>';
                $html .= '<div id="'.$module.'-widget-content" class="widget-content">';
                $html .= $data['content'];
                $html .= '</div></div></div>';
            }
        }
        $html .= '</div>';
        $html .= $this->loadScripts();
		return $html;
	}

    public function getHomeWidgets() {
        $feeds = array(
            'http://www.freepbx.org/rss.xml',
            'http://feeds.feedburner.com/InsideTheAsterisk'
        );
        $out = array();
        foreach($feeds as $feed) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $feed);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            $feed = curl_exec($curl);
            curl_close($curl);
            $xml = simplexml_load_string($feed);
            $content = '<ul>';
            foreach($xml->channel->item as $item) {
                $content .= '<li><a href="'.$item->link.'">'.$item->title.'</a></li>';
            }
            $content .= '</ul>';
            $out[] = array(
                "title" => '<a href="'.$xml->channel->link.'">'.$xml->channel->description.'</a>',
                "content" => $content,
                "size" => 4
            );
        }

        return $out;
    }

	public function doConfigPageInit($display) {
	}

	public function myShowPage() {
	}

	public function getBadge() {
		return false;
	}

	public function getMenuItems() {
		return array(
			"rawname" => "home",
			"name" => "Home",
			"badge" => $this->getBadge(),
			"menu" => false
		);
	}
}
