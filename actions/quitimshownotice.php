<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Show a single notice (and conversations)
 *
 * PHP version 5
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
 * @category  Personal
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

/**
 * Show a single notice
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class QuitimShownoticeAction extends Action
{
    /**
     * Notice object to show
     */
    var $notice = null;

    /**
     * Profile of the notice object
     */
    var $profile = null;

    /**
     * Avatar of the profile of the notice object
     */
    var $avatar = null;

    /**
     * Load attributes based on database arguments
     *
     * Loads all the DB stuff
     *
     * @param array $args $_REQUEST array
     *
     * @return success flag
     */
    protected function prepare(array $args=array())
    {

    	// conversations redirect here too
    	if(isset($args['id'])) {
    		$args['notice'] = $args['id'];
    		}

        if ($this->boolean('ajax')) {
            $this->showAjax();
        } else {
			parent::prepare($args);

			$this->notice = $this->getNotice();

			$cur = common_current_user();

			if (!$this->notice->inScope($this->scoped)) {
				// TRANS: Client exception thrown when trying a view a notice the user has no access to.
				throw new ClientException(_('Not available.'), 403);
			}

			$this->profile = $this->notice->getProfile();

			try {
				$this->avatar = $this->profile->getAvatar(AVATAR_PROFILE_SIZE);
			} catch (Exception $e) {
				$this->avatar = null;
			}
        }

        return true;
    }

    /**
     * Fetch the notice to show. This may be overridden by child classes to
     * customize what we fetch without duplicating all of the prepare() method.
     *
     * @return Notice
     */
    protected function getNotice()
    {
        $id = $this->arg('notice');

        $notice = Notice::getKV('id', $id);

        if (!$notice instanceof Notice) {
            // Did we used to have it, and it got deleted?
            $deleted = Deleted_notice::getKV($id);
            if ($deleted instanceof Deleted_notice) {
                // TRANS: Client error displayed trying to show a deleted notice.
                $this->clientError(_('Notice deleted.'), 410);
            } else {
                // TRANS: Client error displayed trying to show a non-existing notice.
                $this->clientError(_('No such notice.'), 404);
            }
            return false;
        }
        return $notice;
    }

    /**
     * Is this action read-only?
     *
     * @return boolean true
     */
    function isReadOnly($args)
    {
        return true;
    }


    /**
     * Title of the page
     *
     * @return string title of the page
     */
    function title()
    {
        $base = $this->profile->getFancyName();

        // TRANS: Title of the page that shows a notice.
        // TRANS: %1$s is a user name, %2$s is the notice creation date/time.
        return sprintf(_('%1$s\'s status on %2$s'),
                       $base,
                       common_exact_date($this->notice->created));
    }

    /**
     * Handle input
     *
     * Only handles get, so just show the page.
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    protected function handle()
    {
        parent::handle();

        if ($this->boolean('ajax')) {
            $this->showAjax();
        } else {
            if ($this->notice->is_local == Notice::REMOTE) {
                if (!empty($this->notice->url)) {
                    $target = $this->notice->url;
                } else if (!empty($this->notice->uri) && preg_match('/^https?:/', $this->notice->uri)) {
                    // Old OMB posts saved the remote URL only into the URI field.
                    $target = $this->notice->uri;
                } else {
                    // Shouldn't happen.
                    $target = false;
                }
                if ($target && $target != $this->selfUrl()) {
                    common_redirect($target, 301);
                }
            }
            $this->showPage();
        }
    }

    /**
     * Fill the content area of the page
     *
     * Shows a single notice list item.
     *
     * @return void
     */
    function showContent()
    {
//
    }


    function showSections()
    {

    }

    function showStylesheets()
    {

        // We only want quitim stylesheet
        $path = Plugin::staticPath('Quitim','');
        $this->cssLink($path.'css/quitim.css?changed='.date('YmdHis',filemtime(QUITIMDIR.'/css/quitim.css')));


    }

    function showBody()
    {

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
		if($current_user) {
			$bodyclasses .= ' user_in';
			}
        if($current_user->id == $this->profile->id) {
        	$bodyclasses .= ' me';
        	}
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));
        $this->element('div', array('id' => 'spinner-overlay'));

        $this->elementStart('div', array('id' => 'wrap'));

        $this->elementStart('div', array('id' => 'header'));
		$this->showLogo();
		$this->elementStart('div', array('id' => 'topright'));
		$this->element('img', array('id' => 'refresh', 'height' => '30', 'width' => '30', 'src' => Plugin::staticPath('Quitim', 'img/refresh.png')));
		$this->elementEnd('div');
		$this->elementEnd('div');

        $this->elementStart('div', array('id' => 'core'));
        $this->elementStart('div', array('id' => 'aside_primary_wrapper'));
        $this->elementStart('div', array('id' => 'content_wrapper'));
        $this->elementStart('div', array('id' => 'site_nav_local_views_wrapper'));

        $this->elementStart('div', array('id' => 'content'));
        if (common_logged_in()) {
            if (Event::handle('StartShowNoticeForm', array($this))) {
                $this->showNoticeForm();
                Event::handle('EndShowNoticeForm', array($this));
            }
        }

        $this->elementStart('div', array('id' => 'content_inner'));

        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream threaded-view'));
		$this->showNoticesWithCommentsAndFavs();
        $this->elementEnd('div');

        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');

        QuitimFooter::showQuitimFooter();

        $this->elementEnd('div');
        $this->showScripts();
        $this->elementEnd('body');
    }


    function showProfileBlock()
    {
        $block = new QuitimAccountProfileBlock($this, $this->profile);
        $block->show();
    }


	function showNoticesWithCommentsAndFavs()
	{
        if(isset($this->args['id'])) { // full conversations
	        $nl = new QuitimFullThreadedNoticeList($this->notice, $this, $this->profile);
        	}
        else { // notices, i.e. collapsed conversations
	        $nl = new QuitimThreadedNoticeList($this->notice, $this, $this->profile);
        	}
        $cnt = $nl->show();
		if (0 == $cnt) {
			$this->showEmptyListMessage();
		}
	}


    /**
     * Don't show page notice
     *
     * @return void
     */
    function showPageNoticeBlock()
    {
    }

    /**
     * Don't show aside
     *
     * @return void
     */
    function showAside() {
    }

    /**
     * Extra <head> content
     *
     * We show the microid(s) for the author, if any.
     *
     * @return void
     */
    function extraHead()
    {
        $user = User::getKV($this->profile->id);

        if (!$user) {
            return;
        }

        if ($user->emailmicroid && $user->email && $this->notice->uri) {
            $id = new Microid('mailto:'. $user->email,
                              $this->notice->uri);
            $this->element('meta', array('name' => 'microid',
                                         'content' => $id->toString()));
        }

        $this->element('link',array('rel'=>'alternate',
            'type'=>'application/json+oembed',
            'href'=>common_local_url(
                'oembed',
                array(),
                array('format'=>'json','url'=>$this->notice->uri)),
            'title'=>'oEmbed'),null);
        $this->element('link',array('rel'=>'alternate',
            'type'=>'text/xml+oembed',
            'href'=>common_local_url(
                'oembed',
                array(),
                array('format'=>'xml','url'=>$this->notice->uri)),
            'title'=>'oEmbed'),null);

        // Extras to aid in sharing notices to Facebook
        $avatarUrl = $this->profile->avatarUrl(AVATAR_PROFILE_SIZE);
        $this->element('meta', array('property' => 'og:image',
                                     'content' => $avatarUrl));
        $this->element('meta', array('property' => 'og:description',
                                     'content' => $this->notice->content));
    }


    function showAjax()
    {
        $this->startHTML('text/xml;charset=utf-8');
        $this->elementStart('head');
        // TRANS: Title for conversation page.
        $this->element('title', null, _m('TITLE','Notice'));
        $this->elementEnd('head');
        $this->elementStart('body');
        $ct = new QuitimFullThreadedNoticeList($this->notice, $this, $this->scoped);
        $cnt = $ct->show();
        $this->elementEnd('body');
        $this->endHTML();
    }

}

// @todo FIXME: Class documentation missing.
class SingleNoticeItem extends DoFollowListItem
{
    function avatarSize()
    {
        return AVATAR_STREAM_SIZE;
    }
}
