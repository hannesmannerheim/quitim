<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * widget for displaying a list of notices
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
 * @category  UI
 * @package   StatusNet
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('GNUSOCIAL') && !defined('STATUSNET')) { exit(1); }

/**
 * widget for displaying a list of notices
 *
 * There are a number of actions that display a list of notices, in
 * reverse chronological order. This widget abstracts out most of the
 * code for UI for notice lists. It's overridden to hide some
 * data for e.g. the profile page.
 *
 * @category UI
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @see      Notice
 * @see      NoticeListItem
 * @see      ProfileNoticeList
 */
class QuitimThreadedNoticeList extends NoticeList
{
    protected $userProfile;

    function __construct($notice, $out=null, $profile=-1)
    {
        parent::__construct($notice, $out);
        if (is_int($profile) && $profile == -1) {
            $profile = Profile::current();
        }
        $this->userProfile = $profile;
    }

    /**
     * show the list of notices
     *
     * "Uses up" the stream by looping through it. So, probably can't
     * be called twice on the same list.
     *
     * @return int count of notices listed.
     */
    function show()
    {
        $this->out->elementStart('div', array('id' =>'notices_primary'));
        // TRANS: Header for Notices section.
        $this->out->element('h2', null, _m('HEADER','Notices'));
        $this->out->elementStart('ol', array('class' => 'notices threaded-notices xoxo'));

		$notices = $this->notice->fetchAll();
		$total = count($notices);
		$notices = array_slice($notices, 0, NOTICES_PER_PAGE);
		
        $allnotices = self::_allNotices($notices);
    	self::prefill($allnotices);
    	
        $conversations = array();
        
        foreach ($notices as $notice) {

            // Collapse repeats into their originals...
            
            if ($notice->repeat_of) {
                $orig = Notice::getKV('id', $notice->repeat_of);
                if ($orig) {
                    $notice = $orig;
                }
            }
            $convo = $notice->conversation;
            if (!empty($conversations[$convo])) {
                // Seen this convo already -- skip!
                continue;
            }
            $conversations[$convo] = true;

            // Get the convo's root notice
            $root = $notice->conversationRoot($this->userProfile);
            if ($root) {
                $notice = $root;
            }

            try {
                $item = $this->newListItem($notice);
                $item->show();
            } catch (Exception $e) {
                // we log exceptions and continue
                common_log(LOG_ERR, $e->getMessage());
                continue;
            }
        }

        $this->out->elementEnd('ol');
        $this->out->elementEnd('div');

        return $total;
    }

    function _allNotices($notices)
    {
        $convId = array();
        foreach ($notices as $notice) {
            $convId[] = $notice->conversation;
        }
        $convId = array_unique($convId);
        $allMap = Notice::listGet('conversation', $convId);
        $allArray = array();
        foreach ($allMap as $convId => $convNotices) {
            $allArray = array_merge($allArray, $convNotices);
        }
        return $allArray;
    }

    /**
     * returns a new list item for the current notice
     *
     * Recipe (factory?) method; overridden by sub-classes to give
     * a different list item class.
     *
     * @param Notice $notice the current notice
     *
     * @return NoticeListItem a list item for displaying the notice
     */
    function newListItem($notice)
    {
        return new QuitimThreadedNoticeListItem($notice, $this->out, $this->userProfile);
    }
}

/**
 * widget for displaying a single notice
 *
 * This widget has the core smarts for showing a single notice: what to display,
 * where, and under which circumstances. Its key method is show(); this is a recipe
 * that calls all the other show*() methods to build up a single notice. The
 * ProfileNoticeListItem subclass, for example, overrides showAuthor() to skip
 * author info (since that's implicit by the data in the page).
 *
 * @category UI
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 * @see      NoticeList
 * @see      ProfileNoticeListItem
 */
class QuitimThreadedNoticeListItem extends QuitimNoticeListItem
{
    protected $userProfile = null;

    function __construct($notice, $out=null, $profile=null)
    {
        parent::__construct($notice, $out);
        $this->userProfile = $profile;
    }

    function initialItems()
    {
        return 5;
    }

    function showContext()
    {
        // Silence!
    }

    /**
     * finish the notice
     *
     * Close the last elements in the notice list item
     *
     * @return void
     */
    function showEnd()
    {
        $max = $this->initialItems();
        if (!$this->repeat) {
            $stream = new ConversationNoticeStream($this->notice->conversation, $this->userProfile);
            $notice = $stream->getNotices(0, $max + 2);
            $notices = array();
            $cnt = 0;
            $moreCutoff = null;
            while ($notice->fetch()) {
                if (Event::handle('StartAddNoticeReply', array($this, $this->notice, $notice))) {
//                     if ($notice->id == $this->notice->id) {
//                         // Skip!
//                         continue;
//                     }
                    if (substr($notice->content,0,14) == '--no caption--') {
                        // Skip!
                        continue;
                    }
                    $cnt++;
                    if ($cnt > $max) {
                        // boo-yah
                        $moreCutoff = clone($notice);
	                    if (substr($this->notice->content,0,14) != '--no caption--') {                        
	                        $notices[] = $this->notice;
	                        }
                        break;
                    }
                    $notices[] = clone($notice); // *grumble* inefficient as hell
                    Event::handle('EndAddNoticeReply', array($this, $this->notice, $notice));
                }
            }

            if (Event::handle('StartShowThreadedNoticeTail', array($this, $this->notice, &$notices))) {

                $this->out->elementStart('ul', 'notices threaded-replies xoxo');

                $item = new QuitimThreadedNoticeListFavesItem($this->notice, $this->out);
                $hasFaves = $item->show();
                
                // add a fav container even if no faves, to load into with ajax when faving
                if(!$hasFaves) {
                	$this->element('li',array('class' => 'notice-data notice-faves'));
                	}

//                 $item = new ThreadedNoticeListRepeatsItem($this->notice, $this->out);
//                 $hasRepeats = $item->show();

                if ($notices) {                

                    $i=0;
                    foreach (array_reverse($notices) as $notice) {
                        
						if ($notice->id == $this->notice->id && $moreCutoff && $i==0) {
                            $item = new QuitimThreadedNoticeListSubItem($notice, $this->notice, $this->out);
                            $item->show();      							
							$item = new QuitimThreadedNoticeListMoreItem($moreCutoff, $this->out, count($notices));
							$item->show();
							$moreCutoffShown = true;
						} else if ($moreCutoff && $i==0 && !$moreCutoffShown) {
							$item = new QuitimThreadedNoticeListMoreItem($moreCutoff, $this->out, count($notices));
							$item->show();
                            $item = new QuitimThreadedNoticeListSubItem($notice, $this->notice, $this->out);
                            $item->show();      								
						} else {
                            $item = new QuitimThreadedNoticeListSubItem($notice, $this->notice, $this->out);
                            $item->show();      					
						}
                        
                    	$i++;
                    }
                }

                if ($notices || $hasFaves) {
                    // @fixme do a proper can-post check that's consistent
                    // with the JS side
                    if (common_current_user()) {
                        $item = new QuitimThreadedNoticeListReplyItem($this->notice, $this->out);
                        $item->show();
                    }
                }
                $this->out->elementEnd('ul');
                Event::handle('EndShowThreadedNoticeTail', array($this, $this->notice, $notices));
            }
        }

        parent::showEnd();
    }
}

// @todo FIXME: needs documentation.
class QuitimThreadedNoticeListSubItem extends QuitimNoticeListItem
{
    protected $root = null;

    function __construct($notice, $root, $out)
    {
        $this->root = $root;
        parent::__construct($notice, $out);
    }

    function avatarSize()
    {
        return AVATAR_STREAM_SIZE; // @fixme would like something in between
    }

    function showNoticeLocation()
    {
        //
    }

    function showNoticeSource()
    {
        //
    }

    function showContext()
    {
        //
    }

    function getReplyProfiles()
    {
        $all = parent::getReplyProfiles();

        $profiles = array();

        $rootAuthor = $this->root->getProfile();

        foreach ($all as $profile) {
            if ($profile->id != $rootAuthor->id) {
                $profiles[] = $profile;
            }
        }

        return $profiles;
    }

    function showEnd()
    {
        $item = new QuitimThreadedNoticeListInlineFavesItem($this->notice, $this->out);
        $hasFaves = $item->show();
        parent::showEnd();
    }
}

/**
 * Placeholder for loading more replies...
 */
class QuitimThreadedNoticeListMoreItem extends NoticeListItem
{
    protected $cnt;

    function __construct($notice, $out, $cnt)
    {
        parent::__construct($notice, $out);
        $this->cnt = $cnt;
    }

    /**
     * recipe function for displaying a single notice.
     *
     * This uses all the other methods to correctly display a notice. Override
     * it or one of the others to fine-tune the output.
     *
     * @return void
     */
    function show()
    {
        $this->showStart();
        $this->showMiniForm();
        $this->showEnd();
    }

    /**
     * start a single notice.
     *
     * @return void
     */
    function showStart()
    {
        $this->out->elementStart('li', array('class' => 'notice-reply-comments'));
    }

    function showMiniForm()
    {
        $id = $this->notice->conversation;
        $url = common_local_url('conversationreplies', array('id' => $id));

        $n = Conversation::noticeCount($id) - 1;

        // TRANS: Link to show replies for a notice.
        // TRANS: %d is the number of replies to a notice and used for plural.
        $msg = sprintf(_m('Show reply', 'Show all %d replies', $n), $n);

        $this->out->element('a', array('href' => $url), $msg);
    }
}

/**
 * Placeholder for reply form...
 * Same as get added at runtime via SN.U.NoticeInlineReplyPlaceholder
 */
class QuitimThreadedNoticeListReplyItem extends NoticeListItem
{
    /**
     * recipe function for displaying a single notice.
     *
     * This uses all the other methods to correctly display a notice. Override
     * it or one of the others to fine-tune the output.
     *
     * @return void
     */
    function show()
    {
        $this->showStart();
        $this->showMiniForm();
        $this->showEnd();
    }

    /**
     * start a single notice.
     *
     * @return void
     */
    function showStart()
    {
        $this->out->elementStart('li', array('class' => 'notice-reply-placeholder'));
    }

    function showMiniForm()
    {
        $this->out->element('input', array('class' => 'placeholder',
                                           // TRANS: Field label for reply mini form.
                                           'value' => _('Write a reply...')));
    }
}

/**
 * Placeholder for showing faves...
 */
abstract class QuitimNoticeListActorsItem extends QuitimNoticeListItem
{
    /**
     * @return array of profile IDs
     */
    abstract function getProfiles();

    abstract function getListMessage($count, $you);

    function show()
    {
        $links = array();
        $you = false;
        $cur = common_current_user();
        foreach ($this->getProfiles() as $id) {
            if ($cur && $cur->id == $id) {
                $you = true;
                // TRANS: Reference to the logged in user in favourite list.
                array_unshift($links, _m('FAVELIST', 'You'));
            } else {
                $profile = Profile::getKV('id', $id);
                if ($profile) {
                    $links[] = sprintf('<a href="%s">%s</a>',
                                       htmlspecialchars($profile->profileurl),
                                       htmlspecialchars($profile->nickname));
                }
            }
        }

        if ($links) {
            $count = count($links);
            $msg = $this->getListMessage($count, $you);
            $out = sprintf($msg, $this->magicList($links));

            $this->showStart();
            $this->out->raw($out);
            $this->showEnd();
            return $count;
        } else {
            return 0;
        }
    }

    function magicList($items)
    {
        if (count($items) == 0) {
            return '';
        } else if (count($items) == 1) {
            return $items[0];
        } else {
            $first = array_slice($items, 0, -1);
            $last = array_slice($items, -1, 1);
            // TRANS: Separator in list of user names like "Jim, Bob, Mary".
            $separator = _(', ');
            // TRANS: For building a list such as "Jim, Bob, Mary and 5 others like this".
            // TRANS: %1$s is a list of users, separated by a separator (default: ", "), %2$s is the last user in the list.
            return sprintf(_m('FAVELIST', '%1$s and %2$s'), implode($separator, $first), implode($separator, $last));
        }
    }
}

/**
 * Placeholder for showing faves...
 */
class QuitimThreadedNoticeListFavesItem extends QuitimNoticeListActorsItem
{
    function getProfiles()
    {
        $faves = Fave::byNotice($this->notice);
        $profiles = array();
        foreach ($faves as $fave) {
            $profiles[] = $fave->user_id;
        }
        return $profiles;
    }

    function magicList($items)
    {
        if (count($items) > 4) {
            return parent::magicList(array_slice($items, 0, 3));
        } else {
            return parent::magicList($items);
        }
    }

    function getListMessage($count, $you)
    {
        if ($count == 1 && $you) {
            // darn first person being different from third person!
            // TRANS: List message for notice favoured by logged in user.
            return _m('FAVELIST', 'You like this.');
        } else if ($count > 4) {
            // TRANS: List message for when more than 4 people like something.
            // TRANS: %%s is a list of users liking a notice, %d is the number over 4 that like the notice.
            // TRANS: Plural is decided on the total number of users liking the notice (count of %%s + %d).
            return sprintf(_m('%%s and %d others like this.',
                              '%%s and %d others like this.',
                              $count),
                           $count - 3);
        } else {
            // TRANS: List message for favoured notices.
            // TRANS: %%s is a list of users liking a notice.
            // TRANS: Plural is based on the number of of users that have favoured a notice.
            return sprintf(_m('%%s likes this.',
                              '%%s like this.',
                              $count),
                           $count);
        }
    }

    function showStart()
    {
        $this->out->elementStart('li', array('class' => 'notice-data notice-faves'));
    }

    function showEnd()
    {
        $this->out->elementEnd('li');
    }
}

// @todo FIXME: needs documentation.
class QuitimThreadedNoticeListInlineFavesItem extends QuitimThreadedNoticeListFavesItem
{
    function showStart()
    {
        $this->out->elementStart('div', array('class' => 'entry-content notice-faves'));
    }

    function showEnd()
    {
        $this->out->elementEnd('div');
    }
}
