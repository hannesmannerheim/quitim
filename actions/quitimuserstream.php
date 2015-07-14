<?php

/**
 *
 *   QUITIM
 *
 *   h@nnesmannerhe.im
 *
 *   Profile page
 *
 *
 */

class QuitimUserStreamAction extends ShowstreamAction
{

    function isReadOnly($args)
    {
        return true;
    }

    function title()
    {
        return $this->target->getFancyName();
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

        $this->notice = $this->getNoticesButNotReplies(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
		if($current_user) {
			$bodyclasses .= ' user_in';
			}
        if($current_user->id == $this->target->id) {
        	$bodyclasses .= ' me';
        	}
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));
        $this->element('div', array('id' => 'spinner-overlay'));

        $this->elementStart('div', array('id' => 'wrap'));
        QuitimFooter::showQuitimFooter();

        $this->elementStart('div', array('id' => 'header'));
		$this->showLogo();

		$this->elementStart('a', array('href' => '#top'));
		$this->elementStart('h1');
        $this->raw($this->target->getNickname());
		$this->elementEnd('h1');
        $this->elementEnd('a');
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

        $this->elementStart('div', array('id' => 'profileblock'));
		$this->showProfileBlock();

		// subscribe form if logged in and not me
		if(common_current_user() && ($current_user->id != $this->target->id)) {
			$this->elementStart('div', 'profile_subscribe');

			if ($current_user->isSubscribed($this->target)) {
				$usff = new QuitimUnsubscribeForm($this, $this->target);
				$usff->show();
			} else if ($current_user->hasPendingSubscription($this->target)) {
				$sff = new CancelSubscriptionForm($this, $this->target);
				$sff->show();
			} else {
				$sff = new QuitimSubscribeForm($this, $this->target);
				$sff->show();
			}
			$this->elementEnd('div');
		} else {
		   if (Event::handle('StartProfileRemoteSubscribe', array($this, $this->target))) {
				Event::handle('EndProfileRemoteSubscribe', array($this, $this->target));
			}
		}


		$this->elementStart('h2', array('id'=>'noticecountlink'));
		// TRANS: H2 text for user subscription statistics.
        $this->element('a', array('href' => common_local_url('showstream', array('nickname' => $this->target->getNickname())),'class' => ''), _('Images'));
		$this->text(' ');
		$this->text(QuitimImageNoticeCount::imageNoticeCount($this->target));
		$this->elementEnd('h2');


		$this->elementStart('h2', array('id'=>'subscriberscountlink'));
		// TRANS: H2 text for user subscriber statistics.
        $this->element('a', array('href' => common_local_url('quitimfollowers', array('nickname' => $this->target->getNickname())),'class' => ''), _('Followers'));
		$this->text(' ');
		$this->text($this->target->subscriberCount());
		$this->elementEnd('h2');

		$this->elementStart('h2', array('id'=>'subscriptionscountlink'));
		// TRANS: H2 text for user subscription statistics.
		$this->element('a', array('href' => common_local_url('quitimfollowing', array('nickname' => $this->target->getNickname())),'class' => ''), _('Following'));
		$this->text(' ');
		$this->text($this->target->subscriptionCount());
		$this->elementEnd('h2');

        $this->elementEnd('div');


        $this->elementStart('div', array('id' => 'thumb-thread'));
        $this->elementStart('div', array('id' => 'set-thumbnail-view'));
        $this->elementEnd('div');
        $this->elementStart('div', array('id' => 'set-threaded-view'));
        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream thumbnail-view'));
		if($this->notice->N>0) {
			$this->showNoticesWithCommentsAndFavs();
			}
		else {
			// show welcome
			}
        $this->elementEnd('div');

        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');

        $this->elementEnd('div');
        $this->showScripts();
        $this->elementEnd('body');
    }


    function showProfileBlock()
    {
        $block = new QuitimAccountProfileBlock($this, $this->target);
        $block->show();
    }


	function showNoticesWithCommentsAndFavs()
	{
        $nl = new QuitimThreadedNoticeList($this->notice, $this, $this->target);
        $cnt = $nl->show();
		if (0 == $cnt) {
			$this->showEmptyListMessage();
		}
		$this->pagination(
			$this->page > 1, $cnt > NOTICES_PER_PAGE,
			$this->page, 'quitimuserstream', array('nickname' => $this->target->getNickname())
		);
	}


    function getFeeds()
    {
        if (!empty($this->tag)) {
            return array(new Feed(Feed::RSS1,
                                  common_local_url('userrss',
                                                   array('nickname' => $this->target->getNickname(),
                                                         'tag' => $this->tag)),
                                  // TRANS: Title for link to notice feed.
                                  // TRANS: %1$s is a user nickname, %2$s is a hashtag.
                                  sprintf(_('Notice feed for %1$s tagged %2$s (RSS 1.0)'),
                                          $this->target->getNickname(), $this->tag)));
        }

        return array(new Feed(Feed::JSON,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->target->getID(),
                                                    'format' => 'as')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (Activity Streams JSON)'),
                                      $this->target->getNickname())),
                     new Feed(Feed::RSS1,
                              common_local_url('userrss',
                                               array('nickname' => $this->target->getNickname())),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (RSS 1.0)'),
                                      $this->target->getNickname())),
                     new Feed(Feed::RSS2,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->target->getID(),
                                                    'format' => 'rss')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (RSS 2.0)'),
                                      $this->target->getNickname())),
                     new Feed(Feed::ATOM,
                              common_local_url('ApiTimelineUser',
                                               array(
                                                    'id' => $this->target->getID(),
                                                    'format' => 'atom')),
                              // TRANS: Title for link to notice feed.
                              // TRANS: %s is a user nickname.
                              sprintf(_('Notice feed for %s (Atom)'),
                                      $this->target->getNickname())),
                     new Feed(Feed::FOAF,
                              common_local_url('foaf', array('nickname' =>
                                                             $this->target->getNickname())),
                              // TRANS: Title for link to notice feed. FOAF stands for Friend of a Friend.
                              // TRANS: More information at http://www.foaf-project.org. %s is a user nickname.
                              sprintf(_('FOAF for %s'), $this->target->getNickname())));
    }

    function extraHead()
    {
        if ($this->target->bio) {
            $this->element('meta', array('name' => 'description',
                                         'content' => $this->target->bio));
        }

        if ($this->user->emailmicroid && $this->target->getUser()->email) {
            $id = new Microid('mailto:'.$this->target->getUser()->email,
                              $this->selfUrl());
            $this->element('meta', array('name' => 'microid',
                                         'content' => $id->toString()));
        }

        // See https://wiki.mozilla.org/Microsummaries

        $this->element('link', array('rel' => 'microsummary',
                                     'href' => common_local_url('microsummary',
                                                                array('nickname' => $this->target->getNickname()))));

        $rsd = common_local_url('rsd',
                                array('nickname' => $this->target->getNickname()));

        // RSD, http://tales.phrasewise.com/rfc/rsd
        $this->element('link', array('rel' => 'EditURI',
                                     'type' => 'application/rsd+xml',
                                     'href' => $rsd));

        if ($this->page != 1) {
            $this->element('link', array('rel' => 'canonical',
                                         'href' => $this->target->getUrl()));
        }
    }

    /**
     * Get notices but not replies
     *
     * @return array notices
     */
    function getNoticesButNotReplies($offset, $limit, $since_id=0, $max_id=0)
    {

        $notice = new Notice();

        $notice->profile_id = $this->target->id;

        $notice->selectAdd();
        $notice->selectAdd('id');

        Notice::addWhereSinceId($notice, $since_id);
        Notice::addWhereMaxId($notice, $max_id);
        $notice->whereAdd("(reply_to IS NULL)");

        $notice->orderBy('created DESC, id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        $notice->find();

        $ids = array();

        while ($notice->fetch()) {
            $ids[] = $notice->id;
        }

        return Notice::multiGet('id', $ids);
    }

}


class QuitimAccountProfileBlock extends AccountProfileBlock
{
	function showTags()
	{
	// don't show tags
	}
}


class QuitimImageNoticeCount extends Notice
{
    function imageNoticeCount($profile)
    {
        $c = Cache::instance();

        if (!empty($c)) {
            $cnt = $c->get(Cache::key('profile:image_notice_count:'.$profile->id));
            if (is_integer($cnt)) {
                return (int) $cnt;
            }
        }

        $notices = new Notice();
        $notices->profile_id = $profile->id;
        $notices->reply_to = 'NULL';   // only non-replies
        $cnt = (int) $notices->count('distinct id');

        if (!empty($c)) {
            $c->set(Cache::key('profile:image_notice_count:'.$profile->id), $cnt);
        }

        return $cnt;
    }
}
