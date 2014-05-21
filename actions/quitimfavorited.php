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

class QuitimFavoritedAction extends Action
{
    var $notice;
    var $page;    

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
        return _('Popular notices');
    }

    protected function handle()
    {
        parent::handle();

        $this->showPage();
    }

    function showSections()
    {

    }
    
    function showStylesheets()
    {

        $this->cssLink('plugins/Quitim/css/quitim.css');

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
		$this->elementStart('a', array('href' => '#top'));		
		$this->elementStart('h1');
        $this->raw(_('Popular notices'));
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

        QuitimFooter::showQuitimFooter();
        	
        $this->elementEnd('div');
        $this->showScripts();
        $this->elementEnd('body');
    }


	
	function showNoticesWithCommentsAndFavs()
	{
        $nl = new QuitimThreadedNoticeList($this->notice, $this, $this->profile);
        $cnt = $nl->show();
		if (0 == $cnt) {
			$this->showEmptyListMessage();
		}
		$this->pagination(
			$this->page > 1, $cnt > NOTICES_PER_PAGE,
			$this->page, 'quitimfavorited');				
	}   	

}

