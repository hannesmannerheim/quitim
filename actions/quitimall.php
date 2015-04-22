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

class QuitimAllAction extends ProfileAction
{
    var $notice;

    function isReadOnly($args)
    {
        return true;
    }

    protected function profileActionPreparation()
    {
        $stream = new ChronologicalInboxStream($this->target, $this->scoped);

        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE,
                                            NOTICES_PER_PAGE + 1);
    }

    function title()
    {
        if (!empty($this->scoped) && $this->scoped->id == $this->target->id) {
            // TRANS: Title of a user's own start page.
            return _('Home timeline');
        } else {
            // TRANS: Title of another user's start page.
            // TRANS: %s is the other user's name.
            return sprintf(_("%s's home timeline"), $this->target->getBestName());
        }
    }


    function showSections()
    {

    }
    
    function showStylesheets()
    {

        $this->cssLink(Plugin::staticPath('Quitim', 'css/quitim.css'));

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
		if(count($this->notice->N)>0) {
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
        $nl = new QuitimThreadedNoticeList($this->notice, $this, $this->profile);
        $cnt = $nl->show();
		if (0 == $cnt) {
			$this->showEmptyListMessage();
		}
		$this->pagination(
			$this->page > 1, $cnt > NOTICES_PER_PAGE,
			$this->page, 'quitimall', array('nickname' => $this->profile->nickname)
		);				
	}
	
    /**
     * Get notices but not replies
     *
     * @return array notices
     */
    function getNoticesButNotReplies($offset, $limit, $since_id=0, $max_id=0)
    {
        
        $notice = new Notice();

        $notice->profile_id = $this->profile->id;

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
