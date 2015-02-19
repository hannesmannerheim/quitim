<?php
/**
 *
 * The Quitim footer
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Hannes Mannerheim <h@nnesmannerhe.im>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 *
 */

if (!defined('GNUSOCIAL') && !defined('STATUSNET')) { exit(1); }

class QuitimFooter
{
	function showQuitimFooter() {
		$current_user = common_current_user();
		if($current_user) {
			$homeurl = common_local_url('all', array('nickname' => $current_user->nickname));		
			$profileurl = common_local_url('quitimuserstream', array('nickname' => $current_user->nickname));				
			$popularurl = common_local_url('quitimfavorited');
			$notificationsurl = common_local_url('quitimnotifications', array('nickname' => $current_user->nickname));								
			$this->elementStart('div', array('id' => 'fixed-footer'));
			$this->elementStart('a', (strtolower($this->trimmed('action')) == 'quitimall') ? array('id' => 'home-all','class' => 'active','href' => $homeurl) : array('id' => 'home-all','href' => $homeurl));
			$this->elementStart('div');
			$this->elementStart('div');
			$this->elementEnd('div');
			$this->elementEnd('div');
			$this->elementEnd('a');        
			$this->elementStart('a', (strtolower($this->trimmed('action')) == 'quitimfavorited') ? array('id' => 'popular','class' => 'active','href' => $popularurl) : array('id' => 'popular','href' => $popularurl));
			$this->elementStart('div');
			$this->elementStart('div');
			$this->elementEnd('div');
			$this->elementEnd('div');        
			$this->elementEnd('a');    
			$this->elementStart('a', array('id' => 'camera'));
			$this->elementStart('div');
			$this->elementStart('div');
			$this->elementEnd('div');
			$this->elementEnd('div');        
			$this->elementEnd('a');    
			$this->elementStart('a', (strtolower($this->trimmed('action')) == 'quitimnotifications') ? array('id' => 'notifications','class' => 'active', 'href' => $notificationsurl) : array('id' => 'notifications','href' => $notificationsurl));
			$this->elementStart('div');
			$this->elementStart('div');
			$this->elementEnd('div');
			$this->elementEnd('div');                
			$this->elementEnd('a');                                        
			$this->elementStart('a', (strtolower($this->trimmed('action')) == 'quitimuserstream') ? array('id' => 'my-profile','class' => 'active', 'href' => $profileurl) : array('id' => 'my-profile','href' => $profileurl));
			$this->elementStart('div');
			$this->elementStart('div');
			$this->elementEnd('div');
			$this->elementEnd('div');                        
			$this->elementEnd('a');                                
			$this->elementEnd('div');
        	}
        }	
        
}