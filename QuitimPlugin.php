<?php

common_config_set('attachments', 'thumb_width', '306');
common_config_set('attachments', 'thumb_height', '306');
error_reporting(E_ALL ^ E_STRICT); // no PHP Strict Standards errors...

const QUITIMDIR = __DIR__;

class QuitimPlugin extends Plugin {

    function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('quitimnotification', QuitimNotification::schemaDef());
        return true;
    }


    public function onRouterInitialized($m)
    {

        $m->connect('', array('action' => 'quitimfavorited'));
        $m->connect('main/login', array('action' => 'quitimfavorited'));
        $m->connect('main/register', array('action' => 'quitimfavorited'));  // only ajax to fight spam
        $m->connect('favorited',array('action' => 'quitimfavorited'));
        $m->connect('favorited/',array('action' => 'quitimfavorited'));
        $m->connect('main/popular',array('action' => 'quitimfavorited'));
        $m->connect('notice/new', array('action' => 'quitimnewnotice'));
        $m->connect(':nickname/notifications', array('action' => 'quitimnotifications'),
                                array('nickname' => Nickname::DISPLAY_FMT));
		$m->connect('api/quitim/notifications.json',
					array('action' => 'ApiNewNotifications'));
        URLMapperOverwrite::overwrite_variable($m, ':nickname',
                                array('action' => 'showstream'),
                                array('nickname' => Nickname::DISPLAY_FMT),
                                'quitimuserstream');
        URLMapperOverwrite::overwrite_variable($m, ':nickname/',
                                array('action' => 'showstream'),
                                array('nickname' => Nickname::DISPLAY_FMT),
                                'quitimuserstream');
        URLMapperOverwrite::overwrite_variable($m, ':nickname/all',
                                array('action' => 'all'),
                                array('nickname' => Nickname::DISPLAY_FMT),
                                'quitimall');
        URLMapperOverwrite::overwrite_variable($m, 'notice/:notice',
		                        array('action' => 'shownotice'),
        		                array('notice' => '[0-9]+'),
                                'quitimshownotice');
        URLMapperOverwrite::overwrite_variable($m, 'conversation/:id/replies',
		                        array('action' => 'conversationreplies'),
        		                array('id' => '[0-9]+'),
                                'quitimshownotice');
        URLMapperOverwrite::overwrite_variable($m, 'conversation/:id',
								array('action' => 'conversation'),
								array('id' => '[0-9]+'),
                                'quitimshownotice');

        $m->connect('classic/:nickname/all', array('action' => 'all'),
                                array('nickname' => Nickname::DISPLAY_FMT));
        $m->connect('classic/:nickname', array('action' => 'showstream'),
                                array('nickname' => Nickname::DISPLAY_FMT));



    }


    /**
     * Viewport in head-tag
     *
     * @param Action $action Action being shown
     *
     * @return boolean hook flag
     */
    public function onEndShowHeadElements(Action $action)
    {
		$action->element('meta', array('name' => 'viewport',
					     			   'content' => 'width=device-width, initial-scale=1.0, user-scalable=0'));
        return true;
    }


    /**
     * Insert into notification table
     */
    function insertNotification($to_profile_id, $from_profile_id, $ntype, $notice_id=false, $first_notice_id_in_conversation=false)
    {

		// never notify myself
		if($to_profile_id != $from_profile_id) {

			// get first notice in conversation if we don't know it
			if($notice_id && !$first_notice_id_in_conversation) {
				$this_notice = Notice::getKV('id', $notice_id);
				$root_notice = $this_notice->conversationRoot();
				$first_notice_id_in_conversation = $root_notice->id;
				}

			// insert
			$notif = new QuitimNotification();
			$notif->to_profile_id = $to_profile_id;
			$notif->from_profile_id = $from_profile_id;
			$notif->ntype = $ntype;
			$notif->first_notice_id_in_conversation = $first_notice_id_in_conversation;
			$notif->notice_id = $notice_id;
			$notif->created = common_sql_now();
			if (!$notif->insert()) {
				common_log_db_error($notif, 'INSERT', __FILE__);
				return false;
				}
			}
        return true;
    }



    /**
     * Insert likes in notification table
     */
    public function onEndFavorNotice($profile, $notice)
    {
		// only likes for conversation-starters/images in notifications
 		if(empty($notice->reply_to)) {
            $this->insertNotification($notice->profile_id, $profile->id, 'like', $notice->id, $notice->id);
 			}
    }


    /**
     * Remove likes in notification table on dislike
     */
    public function onEndDisfavorNotice($profile, $notice)
    {
		$notif = new QuitimNotification();
		$notif->from_profile_id = $profile->id;
		$notif->notice_id = $notice->id;
		$notif->ntype = 'like';
		$notif->delete();
        return true;
    }



    /**
     * Insert notifications for replies and mentions
     *
     * @return boolean hook flag
     */
    function onStartNoticeDistribute($notice) {

        // not for activity notices
        if($notice->object_type == 'activity') {
            return true;
        }

        // check for reply to insert in notifications
        if($notice->reply_to) {
            $replyparent = $notice->getParent();
            $replyauthor = $replyparent->getProfile();
            if ($replyauthor instanceof Profile) {
                $reply_notification_to = $replyauthor->id;
	            $root_notice = $notice->conversationRoot();
	            $this->insertNotification($replyauthor->id, $notice->profile_id, 'reply', $notice->id, $root_notice->id);

            	// if reply is not to root notice, also notify root notice's profile
            	if($notice->getParent()->id != $root_notice->id) {
		            $rootauthor = $root_notice->getProfile();
		            // if we have not already notified this profile
		            if($rootauthor->id != $replyauthor->id) {
		                $root_reply_notification_to = $rootauthor->id;
			            $this->insertNotification($rootauthor->id, $notice->profile_id, 'reply', $notice->id, $root_notice->id);
			            }
            		}
            	}
            }

        // check for mentions to insert in notifications
        $mentions = common_find_mentions($notice->content, $notice);
        $sender = Profile::getKV($notice->profile_id);
        foreach ($mentions as $mention) {
            foreach ($mention['mentioned'] as $mentioned) {

                // Not from blocked profile
                $mentioned_user = User::getKV('id', $mentioned->id);
                if ($mentioned_user instanceof User && $mentioned_user->hasBlocked($sender)) {
                    continue;
	                }

                // only notify if mentioned user is not already notified for reply
                if($reply_notification_to != $mentioned->id && $root_reply_notification_to != $mentioned->id) {
		            $this->insertNotification($mentioned->id, $notice->profile_id, 'mention', $notice->id);
                	}
            	}
        	}

        return true;
    	}

   /**
     * Delete any notifications tied to deleted notices
     *
     * @return boolean hook flag
     */
    public function onNoticeDeleteRelated($notice)
    {

		$notif = new QuitimNotification();
		$notif->notice_id = $notice->id;
		$notif->delete();

		// also remove all notifications tied to this notice, if it is a root notice
		$notif_root = new QuitimNotification();
		$notif_root->first_notice_id_in_conversation = $notice->id;
		$notif_root->delete();

        return true;
    }

   /**
     * Add notification on subscription, remove on unsubscribe
     *
     * @return boolean hook flag
     */
    public function onEndSubscribe($subscriber, $other)
    {
		if(Subscription::exists($subscriber, $other)) {
			$this->insertNotification($other->id, $subscriber->id, 'follow');
			}

        return true;
    }
    public function onEndUnsubscribe($subscriber, $other)
    {
		if(!Subscription::exists($subscriber, $other)) {
			$notif = new QuitimNotification();
			$notif->to_profile_id = $other->id;
			$notif->from_profile_id = $subscriber->id;
			$notif->ntype = 'follow';
			$notif->delete();
			}

        return true;
    }





    /**
     * Link in a script
     *
     * @param Action $action Action being shown
     *
     * @return boolean hook flag
     */
    public function onEndShowScripts(Action $action)
    {
        $action->script($this->path('js/masonry.pkgd.min.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/masonry.pkgd.min.js')));
        $action->script($this->path('js/load-image.min.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/load-image.min.js')));
        $action->script($this->path('js/filtrr2-0.6.3.min.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/filtrr2-0.6.3.min.js')));
        $action->script($this->path('js/jquery.vintage.min.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/jquery.vintage.min.js')));
        $action->script($this->path('js/jquery.mobile-events.min.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/jquery.mobile-events.min.js')));
        $action->script($this->path('js/jpeg_encoder_basic.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/jpeg_encoder_basic.js')));
        $action->script($this->path('js/quitim.js') . '?changed='.date('YmdHis',filemtime(QUITIMDIR.'/js/quitim.js')));
        return true;
    }



    public function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'Quitim',
                            'version' => '0.1',
                            'author' => 'Hannes Mannerheim',
                            'homepage' => 'https://github.com/hannesmannerheim',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Makes your GNU Social site image focused'));
        return true;
    }



    /**
     *
     * Make sure only "conversation starters" have attachments
     *
     * @return boolean hook flag
     */
    function onStartNoticeSave(&$notice) {

		$img_regexp = '/(http\:\/\/|https\:\/\/)([\wåäö\-\.]+)?(\.)(ac|academy|actor|ad|ae|aero|af|ag|agency|ai|al|am|an|ao|aq|ar|arpa|as|asia|at|au|aw|ax|axa|az|ba|bar|bargains|bb|bd|be|berlin|best|bf|bg|bh|bi|bid|bike|biz|bj|black|blue|bm|bn|bo|boutique|br|bs|bt|build|builders|buzz|bv|bw|by|bz|ca|cab|camera|camp|cards|careers|cat|catering|cc|cd|center|ceo|cf|cg|ch|cheap|christmas|ci|ck|cl|cleaning|clothing|club|cm|cn|co|codes|coffee|cologne|com|community|company|computer|condos|construction|contractors|cool|coop|cr|cruises|cu|cv|cw|cx|cy|cz|dance|dating|de|democrat|diamonds|directory|dj|dk|dm|dnp|do|domains|dz|ec|edu|education|ee|eg|email|enterprises|equipment|er|es|estate|et|eu|events|expert|exposed|farm|fi|fish|fj|fk|flights|florist|fm|fo|foundation|fr|futbol|ga|gallery|gb|gd|ge|gf|gg|gh|gi|gift|gl|glass|gm|gn|gov|gp|gq|gr|graphics|gs|gt|gu|guitars|guru|gw|gy|hk|hm|hn|holdings|holiday|house|hr|ht|hu|id|ie|il|im|immobilien|in|industries|info|ink|institute|int|international|io|iq|ir|is|it|je|jetzt|jm|jo|jobs|jp|kaufen|ke|kg|kh|ki|kim|kitchen|kiwi|km|kn|koeln|kp|kr|kred|kw|ky|kz|la|land|lb|lc|li|lighting|limo|link|lk|london|lr|ls|lt|lu|luxury|lv|ly|ma|maison|management|mango|marketing|mc|md|me|meet|menu|mg|mh|mil|mk|ml|mm|mn|mo|mobi|moda|monash|mp|mq|mr|ms|mt|mu|museum|mv|mw|mx|my|mz|na|nagoya|name|nc|ne|net|neustar|nf|ng|ni|ninja|nl|no|np|nr|nu|nyc|nz|okinawa|om|onl|org|pa|partners|parts|pe|pf|pg|ph|photo|photography|photos|pics|pink|pk|pl|plumbing|pm|pn|post|pr|pro|productions|properties|ps|pt|pub|pw|py|qa|qpon|re|recipes|red|ren|rentals|repair|report|reviews|rich|ro|rs|ru|ruhr|rw|sa|sb|sc|sd|se|sexy|sg|sh|shiksha|shoes|si|singles|sj|sk|sl|sm|sn|so|social|sohu|solar|solutions|sr|st|su|supplies|supply|support|sv|sx|sy|systems|sz|tattoo|tc|td|technology|tel|tf|tg|th|tienda|tips|tj|tk|tl|tm|tn|to|today|tokyo|tools|tp|tr|trade|training|travel|tt|tv|tw|tz|ua|ug|uk|uno|us|uy|uz|va|vacations|vc|ve|ventures|vg|vi|viajes|villas|vision|vn|vote|voting|voto|voyage|vu|wang|watch|webcam|wed|wf|wien|wiki|works|ws|xn\-\-3bst00m|xn\-\-3ds443g|xn\-\-3e0b707e|xn\-\-45brj9c|xn\-\-55qw42g|xn\-\-55qx5d|xn\-\-6frz82g|xn\-\-6qq986b3xl|xn\-\-80ao21a|xn\-\-80asehdb|xn\-\-80aswg|xn\-\-90a3ac|xn\-\-c1avg|xn\-\-cg4bki|xn\-\-clchc0ea0b2g2a9gcd|xn\-\-d1acj3b|xn\-\-fiq228c5hs|xn\-\-fiq64b|xn\-\-fiqs8s|xn\-\-fiqz9s|xn\-\-fpcrj9c3d|xn\-\-fzc2c9e2c|xn\-\-gecrj9c|xn\-\-h2brj9c|xn\-\-i1b6b1a6a2e|xn\-\-io0a7i|xn\-\-j1amh|xn\-\-j6w193g|xn\-\-kprw13d|xn\-\-kpry57d|xn\-\-l1acc|xn\-\-lgbbat1ad8j|xn\-\-mgb9awbf|xn\-\-mgba3a4f16a|xn\-\-mgbaam7a8h|xn\-\-mgbab2bd|xn\-\-mgbayh7gpa|xn\-\-mgbbh1a71e|xn\-\-mgbc0a9azcg|xn\-\-mgberp4a5d4ar|xn\-\-mgbx4cd0ab|xn\-\-ngbc5azd|xn\-\-nqv7f|xn\-\-nqv7fs00ema|xn\-\-o3cw4h|xn\-\-ogbpf8fl|xn\-\-p1ai|xn\-\-pgbs0dh|xn\-\-q9jyb4c|xn\-\-rhqv96g|xn\-\-s9brj9c|xn\-\-unup4y|xn\-\-wgbh1c|xn\-\-wgbl6a|xn\-\-xkc2al3hye2a|xn\-\-xkc2dl3a5ee0h|xn\-\-yfro4i67o|xn\-\-ygbi2ammx|xn\-\-zfr164b|xxx|xyz|ye|yt|za|zm|zone|zw)(\/[\wåäö\%\!\*\'\(\)\;\:\@\&\=\+\$\,\/\?\#\[\]\-\_\.\~]+)?(\/)?(\.jpeg|\.jpg|\.png|\.gif)/';
		preg_match_all($img_regexp, $notice->rendered, $matches);


		// replies should not have attachments, replace attachment url with image url
		if($notice->reply_to && count($matches[0])>0) {
			$notice->rendered = $notice->content;
			foreach($matches[0] as $m) {
				$im = File::getKV('url', $m);
                if ($im instanceof File) {
	        		$imid = $im->id;
					$fileredir = File_redirection::getKV('file_id', $imid);
					$notice->content = str_replace($fileredir->url, '', $notice->content);
					}
				}
			}

		// non-replies should have attachment
		else if(!$matches) {
			throw new ClientException(_('No image!'));
			}

        return true;
    	}



    /**
     *
     * Modify the notice output
     *
     * @return boolean hook flag
     */
    function onEndShowNoticeContent($notice, $out, $scoped) {

//   		$out->raw('<pre>'.print_r($notice->attachments(),true).'</pre>');
//   		$out->raw('<pre>'.print_r($notice,true).'</pre>');


 	}


    /**
     * Notices that are not replies don't need text (content)
     *
     * Check for uploaded base64 images and save them
     *
     * @return boolean hook flag
     */
    function onStartSaveNewNoticeWeb($action, $user, &$content, &$options) {

		// root notices/images
		if(!$action->args['inreplyto']) {

			if(!$content) {
				$content = ' ';
				}

			if(isset($_POST['attach'])) {
				$profile = Profile::current();
				$base64img = $_POST['attach'];
				if(stristr($base64img, 'image/jpeg')) {
					$base64img_mime = 'image/jpeg';
					}
				elseif(stristr($base64img, 'image/png')) {
					// convert to jpg!!
					$base64img_mime = 'image/png';
					}
				$base64img = str_replace('data:image/jpeg;base64,', '', $base64img);
				$base64img = str_replace('data:image/png;base64,', '', $base64img);
				$base64img = str_replace(' ', '+', $base64img);
				$base64img_hash = md5($base64img);
				$base64img = base64_decode($base64img);
				$base64img_basename = basename('quitim-img-'.$base64img_hash);
				$base64img_filename = File::filename($profile, $base64img_basename, $base64img_mime);
				$base64img_path = File::path($base64img_filename);
				$base64img_success = file_put_contents($base64img_path, $base64img);
				$base64img_mimetype = MediaFile::getUploadedMimeType($base64img_path, $base64img_filename);
				$quitim_upload = new MediaFile($profile, $base64img_filename, $base64img_mimetype);
				$content .= ' ' . $quitim_upload->shortUrl();
				}

			}
		// replies
		else {
			if (!isset($action->args['attach'])) {
				// TRANS: Client error displayed trying to send a reply with attachment.
				$action->clientError(_('No images in replies!'));
				}
			}

        return true;
    	}

    /**
     * Quitim's showNoticeForm
     *
     * @return false (to override)
     */
    function onStartShowNoticeFormData($action) {

		$action->out->element('label', array('for' => 'notice_data-text',
										   'id' => 'notice_data-text-label'),
							// TRANS: Title for notice label. %s is the user's nickname.
							sprintf(_('What\'s up, %s?'), $action->user->nickname));
		// XXX: vary by defined max size
		$action->out->element('textarea', array('class' => 'notice_data-text',
											  'cols' => 35,
											  'rows' => 4,
											  'name' => 'status_textarea'),
							($action->content) ? $action->content : '');

		$contentLimit = Notice::maxContent();

		if ($contentLimit > 0) {
			$action->out->element('span',
								array('class' => 'count'),
								$contentLimit);
		}

		if (common_config('attachments', 'uploads')) {
			$action->out->elementStart('label', array('class' => 'notice_data-attach'));
			// TRANS: Input label in notice form for adding an attachment.
			$action->out->text(_('Attach'));
			$action->out->element('input', array('class' => 'notice_data-attach',
											   'type' => 'file',
											   'name' => 'attach',
											   'accept' => 'image/*',
											   // TRANS: Title for input field to attach a file to a notice.
											   'title' => _('Attach a file.')));
			$action->out->elementEnd('label');
		}
		if (!empty($action->actionName)) {
			$action->out->hidden('notice_return-to', $action->actionName, 'returnto');
		}
		$action->out->hidden('notice_in-reply-to', $action->inreplyto, 'inreplyto');

		$action->out->elementStart('div', 'to-selector');
		$toWidget = new ToSelector($action->out,
								   $action->user,
								   (!empty($action->to_group) ? $action->to_group : $action->to_profile));

		$toWidget->show();
		$action->out->elementEnd('div');

		if ($action->profile->shareLocation()) {
			$action->out->hidden('notice_data-lat', empty($action->lat) ? (empty($action->profile->lat) ? null : $action->profile->lat) : $action->lat, 'lat');
			$action->out->hidden('notice_data-lon', empty($action->lon) ? (empty($action->profile->lon) ? null : $action->profile->lon) : $action->lon, 'lon');
			$action->out->hidden('notice_data-location_id', empty($action->location_id) ? (empty($action->profile->location_id) ? null : $action->profile->location_id) : $action->location_id, 'location_id');
			$action->out->hidden('notice_data-location_ns', empty($action->location_ns) ? (empty($action->profile->location_ns) ? null : $action->profile->location_ns) : $action->location_ns, 'location_ns');

			$action->out->elementStart('div', array('class' => 'notice_data-geo_wrap',
												  'data-api' => common_local_url('geocode')));

			// @fixme checkbox method allows no way to change the id without changing the name
			//// TRANS: Checkbox label to allow sharing geo location in notices.
			//$action->out->checkbox('notice_data-geo', _('Share my location'), true);
			$action->out->elementStart('label', 'notice_data-geo');
			$action->out->element('input', array(
				'name' => 'notice_data-geo',
				'type' => 'checkbox',
				'class' => 'checkbox',
				'id' => $action->id() . '-notice_data-geo',
				'checked' => true, // ?
			));
			$action->out->text(' ');
			// TRANS: Field label to add location to a notice.
			$action->out->text(_('Share my location'));
			$action->out->elementEnd('label');

			$action->out->elementEnd('div');
			// TRANS: Text to not share location for a notice in notice form.
			$share_disable_text = _('Do not share my location');
			// TRANS: Timeout error text for location retrieval in notice form.
			$error_timeout_text = _('Sorry, retrieving your geo location is taking longer than expected, please try again later');
			$action->out->inlineScript(' var NoticeDataGeo_text = {'.
				'ShareDisable: ' .json_encode($share_disable_text).','.
				'ErrorTimeout: ' .json_encode($error_timeout_text).
				'}');
		}

		Event::handle('EndShowNoticeFormData', array($action));
        return false;
    	}

}



/**
 * Overwrites variables in URL-mapping
 *
 */
class URLMapperOverwrite extends URLMapper
{
    function overwrite_variable($m, $path, $args, $paramPatterns, $newaction)
    {

        $m->connect($path, array('action' => $newaction), $paramPatterns);

		$regex = URLMapper::makeRegex($path, $paramPatterns);

		foreach($m->variables as $n=>$v)
			if($v[1] == $regex)
				$m->variables[$n][0]['action'] = $newaction;
    }
}
