<?php

class QuitimFollowersAction extends Action
{
    var $page = null;
    var $notice;
    var $profile;

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

        $user_nickname = $this->arg('nickname');
        $this->user = User::getKV('nickname', $user_nickname);
        $this->profile = $this->user->getProfile();
        $this->scoped = Profile::current();

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


    // get all profiles that likes this notice
    function getFollowersProfiles()
    {
        $profile = array();
        try {
            $subs = Subscription::bySubscribed($this->profile->id,0,10000);
            while($subs->fetch()) {
                $profiles[$subs->subscriber] = $subs->subscriber;
            }
        } catch (NoResultException $e) {
            //
        }
        return $profiles;
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
        if(substr($this->profile->nickname,-1) == 's') {
            return sprintf(_("%s' followers"), $this->profile->nickname);
        } else {
            return sprintf(_("%s's followers"), $this->profile->nickname);
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
		$this->elementStart('a', array('href' => '#top'));
		$this->elementStart('h1');
        if(substr($this->profile->nickname,-1) == 's') {
            $this->raw(sprintf(_("%s' followers"), $this->profile->nickname));
        } else {
            $this->raw(sprintf(_("%s's followers"), $this->profile->nickname));
        }
		$this->elementEnd('h1');
        $this->elementEnd('a');
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
        $this->elementStart('div', array('id' => 'quitimprofilelist', 'class' => 'quitimprofilestream'));
        $this->elementStart('div', array('id' => 'profiles_primary'));
        $this->elementStart('ol', array('class' => 'profiles'));


        $profiles = $this->getFollowersProfiles();
		if(count($profiles) > 0) {
            foreach ($profiles as $id) {

                $profile = Profile::getKV('id', $id);
                if ($profile) {
                    $profile_list_item = new QuitimProfileListItem($profile, $this);
                    $profile_list_item->show();
                }
            }
        } else {
			$this->raw(_("No followers"));
			}

        $this->elementEnd('ol');
        $this->elementEnd('div');

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

}
