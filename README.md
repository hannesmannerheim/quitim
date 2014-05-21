Quitim
==========================================

* Author:    Hannes Mannerheim (<h@nnesmannerhe.im>)
* Last mod.: May, 2014
* Version:   0.1
* GitHub:    <https://github.com/hannesmannerheim/Quitim>

Quitim is free  software:  you can  redistribute it  and / or  modify it  
under the  terms of the GNU Affero General Public License as published by  
the Free Software Foundation,  either version three of the License or (at  
your option) any later version.                                            
                                                                           
Quitim is distributed  in hope that  it will be  useful but  WITHOUT ANY  
WARRANTY;  without even the implied warranty of MERCHANTABILTY or FITNESS  
FOR A PARTICULAR PURPOSE.  See the  GNU Affero General Public License for  
more details.                                                              
                                                                           
You should have received a copy of the  GNU Affero General Public License  
along with Quitim. If not, see <http://www.gnu.org/licenses/>.            
                                                                           
About
-----

Quitim makes GNU Social look and work a little like Instagram. 

THIS IS NOT FINISHED SOFTWARE!! Some important things still remain, such as
user settings, commenting design etc etc etc. Only install this if you are curious
or want to help develop it. I mostly uploaded it here now as a backup.


Setup
-----

1. Install GNU Social

2. Disable all plugins except Ostatus and WebFinger in your
://{instance}/panel/plugins panel

3. Put all files in /plugins/Quitim

4. Add `addPlugin('Quitim');` and  `addPlugin('InfiniteScroll');` to your 
/config.php file.

5. In lib/framework.php, set these variables like this:

		define('AVATAR_PROFILE_SIZE', 192);
		define('AVATAR_STREAM_SIZE', 96);
		define('AVATAR_MINI_SIZE', 48);
		define('NOTICES_PER_PAGE', 10);

	### Show nicknames instead of full names (optional)

	6. In `function showAuthor()` in lib/noticelistitem.php change

			$this->out->element('span',array('class' => 'fn'), $this->profile->getStreamName());

		to

			$this->out->element('span',array('class' => 'fn'), $this->profile->nickname);
	
	7. At ~line 440 in lib/threadednoticelist.php change

			$links[] = sprintf('<a href="%s">%s</a>',
							   htmlspecialchars($profile->profileurl),
							   htmlspecialchars($profile->getBestName()));

		to 

			$links[] = sprintf('<a href="%s">%s</a>',
						   htmlspecialchars($profile->profileurl),
						   htmlspecialchars($profile->nickname));

	8. In classes/User.php comment out the contents of `function streamNicknames()` and 
	make it always return true (~line 986):

			function streamNicknames()
			{

			//	if (common_config('oldschool', 'enabled')) {
			//		$osp = Old_school_prefs::getKV('user_id', $this->id);
			//		if (!empty($osp)) {
			//			return $osp->stream_nicknames;
			//		}
			//	}
			//	return false;

				return true;

			}