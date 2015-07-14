<?php

class QuitimLikersAction extends Action
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

        $notice_id = $this->arg('notice');
        $this->notice = Notice::getKV('id', $notice_id);
        $this->profile = $this->notice->getProfile();
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
    function getLikerProfiles()
    {
        $faves = Fave::byNotice($this->notice);
        $profiles = array();
        foreach ($faves as $fave) {
            $profiles[] = $fave->user_id;
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
        return sprintf(_("Likes for %s's image"), $this->profile->nickname);
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
        $this->raw(_('Likers'));
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


        $profiles = $this->getLikerProfiles();
		if(count($profiles) > 0) {
            foreach ($profiles as $id) {

                $profile = Profile::getKV('id', $id);
                if ($profile) {
                    $profile_list_item = new QuitimProfileListItem($profile, $this);
                    $profile_list_item->show();
                }
            }
        } else {
			$this->raw(_("No likes"));
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
