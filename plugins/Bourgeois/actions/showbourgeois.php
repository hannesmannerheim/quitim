<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * List of replies
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

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * List of replies
 *
 * @category Personal
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class ShowbourgeoisAction extends ShowstreamAction
{
    function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for first page of favourite notices of a user.
            // TRANS: %s is the user for whom the favourite notices are displayed.
            return sprintf(_('notices marked as bourgeois by %s'), $this->getTarget()->getNickname());
        } else {
            // TRANS: Title for all but the first page of favourite notices of a user.
            // TRANS: %1$s is the user for whom the favourite notices are displayed, %2$d is the page number.
            return sprintf(_('notices marked as bourgeois by %1$s, page %2$d'),
                           $this->getTarget()->getNickname(),
                           $this->page);
        }
    }

    public function getStream()
    {
        $own = $this->scoped instanceof Profile ? $this->scoped->sameAs($this->getTarget()) : false;
        return new BourgeoisNoticeStream($this->getTarget()->getID(), $own);
    }

    function getFeeds()
    {
        return array(new Feed(Feed::RSS1,
                              common_local_url('bourgeoisrss',
                                               array('nickname' => $this->user->nickname)),
                              // TRANS: Feed link text. %s is a username.
                              sprintf(_('Feed for notices marked as bourgeois by %s (RSS 1.0)'),
                                      $this->user->nickname)));
    }

    function showEmptyListMessage()
    {
        if (common_logged_in()) {
            $current_user = common_current_user();
            if ($this->user->id === $current_user->id) {
                // TRANS: Text displayed instead of favourite notices for the current logged in user that has no favourites.
                $message = _('You haven\'t marked any notices as bourgeois yet. Click the "mark as bourgeois" button on notices you think is bourgeois.');
            } else {
                // TRANS: Text displayed instead of favourite notices for a user that has no favourites while logged in.
                // TRANS: %s is a username.
                $message = sprintf(_('%s hasn\'t marked any notices as bourgeois yet. Post something bourgeois :)'), $this->user->nickname);
            }
        }
        else {
                // TRANS: Text displayed instead of favourite notices for a user that has no favourites while not logged in.
                // TRANS: %s is a username, %%%%action.register%%%% is a link to the user registration page.
                // TRANS: (link text)[link] is a Mark Down link.
            $message = sprintf(_('%s hasn\'t marked any notices as bourgeois yet. Why not [register an account](%%%%action.register%%%%) and then post something bourgeois :)'), $this->user->nickname);
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    /**
     * Show the content
     *
     * A list of notices that this user has marked as a favorite
     *
     * @return void
     */
    function showNotices()
    {
        $nl = new BourgeoisNoticeList($this->notice, $this);

        $cnt = $nl->show();
        if (0 == $cnt) {
            $this->showEmptyListMessage();
        }

        $this->pagination($this->page > 1, $cnt > NOTICES_PER_PAGE,
                          $this->page, 'showbourgeois',
                          array('nickname' => $this->getTarget()->getNickname()));
    }

    function showPageNotice() {
        // TRANS: Page notice for show favourites page.
        $this->element('p', 'instructions', _('This is a way to share what you like.'));
    }
}

class BourgeoisNoticeList extends NoticeList
{
    function newListItem($notice)
    {
        return new BourgeoisNoticeListItem($notice, $this->out);
    }
}

// All handled by superclass
class BourgeoisNoticeListItem extends DoFollowListItem
{
}
