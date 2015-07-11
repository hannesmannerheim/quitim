<?php

class QuitimNotificationsAction extends Action
{
    var $page = null;
    var $notice;

    /**
     * Prepare the object
     *
     * Check the input values and initialize the object.
     * Shows an error page on bad input.
     *
     * @param array $args $_REQUEST data
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);

        $nickname = common_canonical_nickname($this->arg('nickname'));

        $this->user = User::getKV('nickname', $nickname);
        $current_user = Profile::current();


        if (!$this->user) {
            // TRANS: Client error displayed when trying to reply to a non-exsting user.
            $this->clientError(_('No such user.'));
        }

        if ($this->user->id != $current_user->id) {
            // TRANS: Client error displayed when trying to access someone elses notifications
            $this->clientError(_('Not available.'));
        }

        $profile = $this->user->getProfile();

        if (!$profile) {
            // TRANS: Error message displayed when referring to a user without a profile.
            $this->serverError(_('User has no profile.'));
        }

        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        common_set_returnto($this->selfUrl());

        $stream = new NotificationStream($current_user);

        $this->notifications = $stream->getNotifications(($this->page-1) * NOTICES_PER_PAGE,
                                            NOTICES_PER_PAGE + 1, false, false);

        if($this->page > 1 && $this->notifications->N == 0){
            // TRANS: Server error when page not found (404)
            $this->serverError(_('No such page.'),$code=404);
        }

        return true;
    }

    /**
     * Handle a request
     *
     * Just show the page. All args already handled.
     *
     * @param array $args $_REQUEST data
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);
        $this->showPage();
    }

    /**
     * Title of the page
     *
     * Includes name of user and page number.
     *
     * @return string title of page
     */
    function title()
    {
        if ($this->page == 1) {
            // TRANS: Title for first page of replies for a user.
            // TRANS: %s is a user nickname.
            return sprintf(_("Notifications for %s"), $this->user->nickname);
        } else {
            // TRANS: Title for all but the first page of replies for a user.
            // TRANS: %1$s is a user nickname, %2$d is a page number.
            return sprintf(_('Notifications for %1$s, page %2$d'),
                           $this->user->nickname,
                           $this->page);
        }
    }


    function showBody()
    {

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
		if($current_user) {
			$bodyclasses .= ' user_in';
			}
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));
        $this->element('div', array('id' => 'spinner-overlay'));

        $this->elementStart('div', array('id' => 'wrap'));
        QuitimFooter::showQuitimFooter();

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
        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream notification-stream threaded-view'));
        $this->elementStart('div', array('id' => 'notices_primary'));
        $this->elementStart('ol', array('class' => 'notices notifications'));

		$newstarted=false;
		$newended=false;
		if($this->notifications->_count > 0) {
			foreach($this->notifications->_items as $notification){

				$first_seen_class = '';
				if(!$newstarted && $notification->is_seen == 0) {
			        $this->elementStart('li', array('id' => 'new-notifications-start'));
					$this->raw(_("New notifications"));
			        $this->elementEnd('li');
					$newstarted=true;
					}
				elseif($newstarted && !$newended && $notification->is_seen == 1) {
					$newended=true;
					$first_seen_class = 'first-seen';
					}

				// mark as seen
				if($notification->is_seen == 0) {
					$notification->is_seen = 1;
					$notification->update();
					$new_notice_class = 'new';
					}
				else {
					$new_notice_class = '';
					}

				$from_profile = Profile::getKV($notification->from_profile_id);
				$first_notice_id_in_conversation = Notice::getKV($notification->first_notice_id_in_conversation);

				$this->elementStart('li', array('id' => 'notice-'.$notification->id, 'class' => 'notice notification '.$new_notice_class.$first_seen_class));
		        $this->elementStart('div', array('class' => 'entry-title'));

				if($notification->ntype == "follow") {
					$this->showAuthorAndNotificationText($notification, $from_profile, _("is following you"));
					}
				elseif($notification->ntype == "like") {
					$this->showThumb($first_notice_id_in_conversation);
					$this->showAuthorAndNotificationText($notification, $from_profile, _("likes your image"));
					}
				elseif($notification->ntype == "reply") {
					$this->showThumb($first_notice_id_in_conversation);
					$comment = Notice::getKV($notification->notice_id);
					$this->showAuthorAndNotificationText($notification, $from_profile, _("has commented on your image: ").$comment->rendered);
					}
				elseif($notification->ntype == "mention") {
					$this->showThumb($first_notice_id_in_conversation);
					$comment = Notice::getKV($notification->notice_id);
					$this->showAuthorAndNotificationText($notification, $from_profile, _("has mentioned you in a comment: ").$comment->rendered);
					}

		        $this->elementEnd('div');
		        $this->elementEnd('li');
				}

			}
		else {
			$this->raw(_("No notifications"));
			}

        $this->elementEnd('ol');
        $this->elementEnd('div');



        $this->pagination($this->page > 1, $this->notifications->_count > NOTICES_PER_PAGE,
                          $this->page, 'quitimnotifications',
                          array('nickname' => $this->user->nickname));


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

    /**
     * Show the content
     *
     * A list of notices that are replies to the user, plus pagination.
     *
     * @return void
     */
    function showContent()
    {
	//
    }



    function isReadOnly($args)
    {
        return true;
    }

    function showStylesheets()
    {

        $this->cssLink(Plugin::staticPath('Quitim', 'css/quitim.css'));

    }

    function showAuthorAndNotificationText($notification, $profile, $notificationtext)
    {
        $this->elementStart('div', 'author');

        $this->elementStart('span', 'vcard author');

        $attrs = array('href' => $profile->profileurl,
                       'class' => 'url',
                       'title' => $profile->nickname);

        $this->elementStart('a', $attrs);
        $this->showAvatar($profile);
        $this->text(' ');
        $this->element('span',array('class' => 'fn'), $profile->nickname);
        $this->elementEnd('a');

        $this->elementEnd('span');
        $this->elementStart('span', array('class' => 'notificationtext'));
		$this->raw($notificationtext);
        $this->elementEnd('span');
        $dt = common_date_iso8601($notification->created);
        $this->element('abbr', array('class' => 'published','title' => $dt),'  ·  '.common_date_string($notification->created));
        $this->elementEnd('div');
    }

    function showAvatar($profile)
    {

        $avatarUrl = $profile->avatarUrl(AVATAR_STREAM_SIZE);

        $this->element('img', array('src' => $avatarUrl,
                                         'class' => 'avatar photo',
                                         'width' => AVATAR_STREAM_SIZE,
                                         'height' => AVATAR_STREAM_SIZE,
                                         'alt' =>
                                         ($profile->fullname) ?
                                         $profile->fullname :
                                         $profile->nickname));
    }

	function showThumb($first_notice_id_in_conversation) {
		$att = $first_notice_id_in_conversation->attachments();
		foreach ($att as $attachment) {
			$thumbnail = File_thumbnail::getKV('file_id', $attachment->id);
			if ($thumbnail) {
				$this->elementStart('a', array('class' => 'thumb', 'href' => $first_notice_id_in_conversation->uri));
				$this->element('img', array('alt' => '', 'src' => $thumbnail->url, 'width' => $thumbnail->width, 'height' => $thumbnail->height));
				$this->elementEnd('a');
				break;
				}
			}
		}

}
