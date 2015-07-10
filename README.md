Quitim
==========================================

* Author:    Hannes Mannerheim (<h@nnesmannerhe.im>)
* Last mod.: July, 2015
* Version:   0.2-alpha
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

2. It's probably best to disable all plugins except EmailAuthentication, Ostatus, OpportunisticQM, RegisterThrottle and WebFinger in your
://{instance}/panel/plugins panel

3. Put all files in local/plugins/Quitim

4. Add `addPlugin('Quitim');` and  `addPlugin('InfiniteScroll');` to your
/config.php file.

5. In lib/framework.php, set these constants like this:

		define('AVATAR_PROFILE_SIZE', 192);
		define('AVATAR_STREAM_SIZE', 96);
		define('AVATAR_MINI_SIZE', 48);
