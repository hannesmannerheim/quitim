<?php

/**
 *
 *   QUITIM
 *
 *   h@nnesmannerhe.im
 *
 *   Me and my friends stream (/all)
 *
 *
 */

class QuitimAllAction extends AllAction
{

    function showSections()
    {
        // We don't want these
    }

    function showStylesheets()
    {
        // We only want quitim stylesheet
        $path = Plugin::staticPath('Quitim','');
        $this->cssLink($path.'css/quitim.css?changed='.date('YmdHis',filemtime(QUITIMDIR.'/css/quitim.css')));

    }

    function showBody()
    {

        $stream = new ChronologicalInboxStream($this->target, $this->scoped);

        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE,
                                            NOTICES_PER_PAGE + 1);

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
        if ($this->scoped instanceof Profile) {
            $bodyclasses .= ' user_in';
            if ($this->scoped->id === $this->target->id) {
                $bodyclasses .= ' me';
        	}
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

        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream threaded-view'));
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
			$this->page, 'quitimall', array('nickname' => $this->target->getNickname())
		);
	}



}
