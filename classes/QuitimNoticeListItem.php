<?php

if (!defined('GNUSOCIAL')) { exit(1); }

class QuitimNoticeListItem extends NoticeListItem
{
    // Add changes that might be necessary later on or just keep this as an empty intermediate class

    function showStart()
    {

        // see if this is an "empty" notice with only image attachments
        $attachments = $this->notice->attachments();
        $content_stripped_of_attachments = $this->notice->content;
        $image_attachments_num = 0;
 		foreach($attachments as $attachment) {
 			$attachment_type = substr($attachment->mimetype, 0, strpos($attachment->mimetype,'/'));
 			if(empty($this->notice->reply_to) && $attachment_type == 'image' && $attachment instanceof File) {
                $image_attachments_num++;
                $content_stripped_of_attachments = trim(str_replace(File_redirection::getKV('file_id',$attachment->id)->url,'',$content_stripped_of_attachments));
            }
		}


        if (Event::handle('StartOpenNoticeListItemElement', array($this))) {
            $id = (empty($this->repeat)) ? $this->notice->id : $this->repeat->id;
            $class = 'h-entry notice';
            if ($this->notice->scope != 0 && $this->notice->scope != 1) {
                $class .= ' limited-scope';
            }
            if (!empty($this->notice->source)) {
                $class .= ' notice-source-'.$this->notice->source;
            }
            if($content_stripped_of_attachments == '') {
                $class .= ' empty-text';
            }
            if(isset($this->notice->is_conversation_starter)) {
                $class .= ' conversation-starter';
            }
            if($image_attachments_num == 0) {
                $class .= ' no-images';
            }
            $id_prefix = (strlen($this->id_prefix) ? $this->id_prefix . '-' : '');
            $this->out->elementStart($this->item_tag, array('class' => $class,
                                                 'id' => "${id_prefix}notice-${id}"));
            Event::handle('EndOpenNoticeListItemElement', array($this));
        }
    }


    function showAuthor()
    {
        $attrs = array('href' => $this->profile->profileurl,
                       'class' => 'h-card p-author',
                       'title' => $this->profile->getStreamName());

        if (Event::handle('StartShowNoticeItemAuthor', array($this->profile, $this->out, &$attrs))) {
            $this->out->elementStart('a', $attrs);
            $this->showAvatar($this->profile);
            $this->out->text($this->profile->getNickname());
            $this->out->elementEnd('a');
            Event::handle('EndShowNoticeItemAuthor', array($this->profile, $this->out));
        }
    }


    function showContent()
    {

        $this->out->elementStart('article', array('class' => 'e-content'));

        $attachments = $this->notice->attachments();

 		foreach($attachments as $attachment) {
 			$attachment_type = substr($attachment->mimetype, 0, strpos($attachment->mimetype,'/'));

 			// we only show conversation starting images
 			if(empty($this->notice->reply_to) && $attachment_type == 'image' && $attachment instanceof File) {

 				// show full image if small
 				if($attachment->width < 1000) {
                    $thumb_url = $attachment->url;
				// thumbnail scaled to 1000px wide if big
                } else {
                    $ratio = $attachment->width/$attachment->height;
                    $thumb = $attachment->getThumbnail(1000, 1000/$ratio);
                    $thumb_url = $thumb->url;
                }
                $this->out->raw('<div class="quitim-notice"><a class="link" href="'.$this->notice->getUrl().'"><img src="'.$thumb_url.'" /></a><img class="no-link" src="'.$thumb_url.'" /></div>');
			}
		}

        // don't know why i had to do this, but when QuitimNoticeListItem is used from quitimnewnotice.php it can't find this clas...
        // but we don't need it then, since it's always the question about getting a comment with ajax then.
        if(class_exists('QuitimThreadedNoticeListFavesItem')) {
            $item = new QuitimThreadedNoticeListFavesItem($this->notice, $this->out);
            $hasFaves = $item->show();
        } else {
            $hasFaves = false;
        }

        // add a fav container even if no faves, to load into with ajax when faving
        if(!isset($hasFaves) || !$hasFaves) {
        	$this->element('div',array('class' => 'notice-data notice-faves'));
        	}

        $this->out->elementStart('div', 'first-comment');
        $this->showAuthor();
        $this->out->elementStart('span', 'quitim-notice-text');
        if ($this->maxchars > 0 && mb_strlen($this->notice->content) > $this->maxchars) {
            $this->out->text(mb_substr($this->notice->content, 0, $this->maxchars) . '[…]');
        } elseif ($this->notice->rendered) {
            $this->out->raw($this->notice->rendered);
        }
        $this->out->elementEnd('span');
        $this->out->elementEnd('div');

        $this->out->elementEnd('article');
    }

}
