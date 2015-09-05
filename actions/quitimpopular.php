<?php

/**
 *
 *   QUITIM
 *
 *   h@nnesmannerhe.im
 *
 *   Popular page
 *
 *
 */

class QuitimPopularAction extends FormAction
{
    var $notice;
    var $page;

    protected $needLogin = false;

    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        $stream = new PopularNoticeStream(Profile::current());
        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE+1);

        return true;
    }

    function isReadOnly($args)
    {
        return true;
    }

    function title()
    {
       return _('Quitim moments');
    }

    protected function handle()
    {

        parent::handle();

    }

    protected function doPost()
    {

    }

    public function showPageNotice()
    {

    }

    public function showInstructions()
    {

    }


    public function showForm($msg=null, $success=false)
    {

    }


    function showSections()
    {

    }

    function showStylesheets()
    {

        // We only want quitim stylesheet
        $path = Plugin::staticPath('Quitim', '');
        $this->cssLink($path.'css/quitim.css?changed='.date('YmdHis',filemtime(QUITIMDIR.'/css/quitim.css')));


    }

    function showBody()
    {

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
		if($current_user) {
			$bodyclasses .= ' user_in';
        } else {
            $bodyclasses .= ' logged-out';
        }
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));
        $this->element('div', array('id' => 'spinner-overlay'));
        $this->element('div', array('id' => 'popup-register'));

        $this->elementStart('div', array('id' => 'wrap'));
        QuitimFooter::showQuitimFooter();

        $this->elementStart('div', array('id' => 'header'));
		$this->elementStart('a', array('href' => '#top'));
		$this->elementStart('h1');
        $this->raw(_('Quitim moments'));
		$this->elementEnd('h1');
        $this->elementEnd('a');

		$this->elementStart('div', array('id' => 'topright'));
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

        $this->elementStart('div', array('id' => 'thumb-thread'));
        $this->elementStart('div', array('id' => 'set-thumbnail-view'));
        $this->elementEnd('div');
        $this->elementStart('div', array('id' => 'set-threaded-view'));
        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream thumbnail-view'));
		$this->showNoticesWithCommentsAndFavs();
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



	function showNoticesWithCommentsAndFavs()
	{

        $nl = new QuitimThreadedNoticeList($this->notice, $this);
        $cnt = $nl->show();
		$this->pagination(
			$this->page > 1, $cnt > NOTICES_PER_PAGE,
			$this->page, 'quitimfavorited');
	}

}
