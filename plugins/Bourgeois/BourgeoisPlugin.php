<?php
/*
 * GNU Social - a federating social network
 * Copyright (C) 2014, Free Software Foundation, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * @package     Activity
 * @maintainer  Mikael Nordfeldth <mmn@hethane.se>
 */
class BourgeoisPlugin extends ActivityVerbHandlerPlugin
{
    protected $email_notify_bourgeois = 1;

    public function tag()
    {
        return 'bourgeois';
    }

    public function types()
    {
        return array();
    }

    public function verbs()
    {
        return array('http://activitystrea.ms/schema/1.0/bourgeois','http://activitystrea.ms/schema/1.0/unmarkbourgeois');
    }

    public function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('bourgeois', Bourgeois::schemaDef());
        return true;
    }

    public function initialize()
    {
        common_config_set('email', 'notify_bourgeois', $this->email_notify_bourgeois);
    }



    public function onRouterInitialized(URLMapper $m)
    {
        // Web UI actions
        $m->connect('main/markbourgeois', array('action' => 'markbourgeois'));
        $m->connect('main/unmarkbourgeois', array('action' => 'unmarkbourgeois'));

		$m->connect('bourgeoisrss', array('action' => 'bourgeoisrss'));
		$m->connect('markedbourgeois/', array('action' => 'bourgeois'));
		$m->connect('markedbourgeois', array('action' => 'bourgeois'));

		$m->connect(':nickname/bourgeois',
					array('action' => 'showbourgeois'),
					array('nickname' => Nickname::DISPLAY_FMT));
		$m->connect(':nickname/bourgeois/rss',
					array('action' => 'bourgeoisrss'),
					array('nickname' => Nickname::DISPLAY_FMT));

    }

    // FIXME: Set this to abstract public in lib/activityhandlerplugin.php ddwhen all plugins have migrated!
    protected function saveObjectFromActivity(Activity $act, Notice $stored, array $options=array())
    {
        assert($this->isMyActivity($act));

        // If empty, we should've created it ourselves on our node.
        if (!isset($options['created'])) {
            $options['created'] = !empty($act->time) ? common_sql_date($act->time) : common_sql_now();
        }

        // We must have an objects[0] here because in isMyActivity we require the count to be == 1
        $actobj = $act->objects[0];

        $object = Bourgeois::saveActivityObject($actobj, $stored);
        $stored->object_type = ActivityUtils::resolveUri($object->getObjectType(), true);

        return $object;
    }

    // FIXME: Put this in lib/activityhandlerplugin.php when we're ready
    //          with the other microapps/activityhandlers as well.
    //          Also it should be StartNoticeAsActivity (with a prepped Activity, including ->context etc.)
    public function onEndNoticeAsActivity(Notice $stored, Activity $act, Profile $scoped=null)
    {
        if (!$this->isMyNotice($stored)) {
            return true;
        }

        common_debug('Extending activity '.$stored->id.' with '.get_called_class());
        $this->extendActivity($stored, $act, $scoped);
        return false;
    }

    public function extendActivity(Notice $stored, Activity $act, Profile $scoped=null)
    {
        Bourgeois::extendActivity($stored, $act, $scoped);
    }

    public function activityObjectFromNotice(Notice $notice)
    {
        $bourgeois = Bourgeois::fromStored($notice);
        return $bourgeois->asActivityObject();
    }

    public function deleteRelated(Notice $notice)
    {
        try {
            $bourgeois = Bourgeois::fromStored($notice);
            $bourgeois->delete();
        } catch (NoResultException $e) {
            // Cool, no problem. We wanted to get rid of it anyway.
        }
    }


    public function onNoticeDeleteRelated(Notice $notice)
    {
        parent::onNoticeDeleteRelated($notice);

        // The below algorithm is because we want to delete bourgeois
        // activities on any notice which _has_ been marked as bourgeois, and not as
        // in the parent function only ones that _are_ marked as bourgeois.

        $bourgeois = new Bourgeois();
        $bourgeois->notice_id = $notice->id;

        if ($bourgeois->find()) {
            while ($bourgeois->fetch()) {
                $bourgeois->delete();
            }
        }

        $bourgeois->free();
    }

    public function onProfileDeleteRelated(Profile $profile, array &$related)
    {
        $bourgeois = new Bourgeois();
        $bourgeois->user_id = $profile->id;
        $bourgeois->delete();    // Will perform a DELETE matching "user_id = {$user->id}"
        $bourgeois->free();

        Bourgeois::blowCacheForProfileId($profile->id);
        return true;
    }

    public function onStartNoticeListPrefill(array &$notices, array $notice_ids, Profile $scoped=null)
    {
        // prefill array of objects, before pluginfication it was Notice::fillBourgeois($notices)
        Bourgeois::fillBourgeois($notice_ids);

        // DB caching
        if ($scoped instanceof Profile) {
            Bourgeois::pivotGet('notice_id', $notice_ids, array('user_id' => $scoped->id));
        }
    }

    /**
     * show the "bourgeois" form in the notice options element
     * FIXME: Don't let a NoticeListItemAdapter slip in here (or extend that from NoticeListItem)
     *
     * @return void
     */
    public function onStartShowNoticeOptionItems($nli)
    {
        if (Event::handle('StartShowBourgeoisForm', array($nli))) {
            $scoped = Profile::current();
            if ($scoped instanceof Profile) {
                if (Bourgeois::existsForProfile($nli->notice, $scoped)) {
                    $unmarkourgeois = new UnmarkBourgeoisForm($nli->out, $nli->notice);
                    $unmarkourgeois->show();
                } else {
                    $markourgeois = new MarkBourgeoisForm($nli->out, $nli->notice);
                    $markourgeois->show();
                }
            }
            Event::handle('EndShowBourgeoisForm', array($nli));
        }
    }

    protected function showNoticeListItem(NoticeListItem $nli)
    {
        // pass
    }
    public function openNoticeListItemElement(NoticeListItem $nli)
    {
        // pass
    }
    public function closeNoticeListItemElement(NoticeListItem $nli)
    {
        // pass
    }

    public function onAppendUserActivityStreamObjects(UserActivityStream $uas, array &$objs)
    {
        $bourgeois = new Bourgeois();
        $bourgeois->user_id = $uas->getUser()->id;

        if (!empty($uas->after)) {
            $bourgeois->whereAdd("modified > '" . common_sql_date($uas->after) . "'");
        }

        if ($bourgeois->find()) {
            while ($bourgeois->fetch()) {
                $objs[] = clone($bourgeois);
            }
        }

        return true;
    }

    public function onEndShowThreadedNoticeTailItems(NoticeListItem $nli, Notice $notice, &$threadActive)
    {
        if ($nli instanceof ThreadedNoticeListSubItem) {
            // The sub-items are replies to a conversation, thus we use different HTML elements etc.
            $item = new ThreadedNoticeListInlineBourgeoisItem($notice, $nli->out);
        } else {
            $item = new ThreadedNoticeListBourgeoisItem($notice, $nli->out);
        }
        $threadActive = $item->show() || $threadActive;
        return true;
    }

    public function onEndMarkBourgeoisNotice(Profile $actor, Notice $target)
    {
        try {
            $notice_author = $target->getProfile();
            // Don't notify ourselves if we marked our own notice as bourgeois,
            // or if it's a remote user (since we don't know their email addresses etc.)
            if ($notice_author->id == $actor->id || !$notice_author->isLocal()) {
                return true;
            }
            $local_user = $notice_author->getUser();
            mail_notify_bourgeois($local_user, $actor, $target);
        } catch (Exception $e) {
            // Mm'kay, probably not a local user. Let's skip this bourgeois notification.
        }
    }


    // Form stuff (settings etc.)

    public function onEndEmailFormData(Action $action, Profile $scoped)
    {
        $emailbourgeois = $scoped->getConfigPref('email', 'notify_bourgeois') ? 1 : 0;

        $action->elementStart('li');
        $action->checkbox('email-notify_bourgeois',
                        // TRANS: Checkbox label in e-mail preferences form.
                        _('Send me email when someone marks my notice as bourgeois.'),
                        $emailbourgeois);
        $action->elementEnd('li');

        return true;
    }

    public function onStartEmailSaveForm(Action $action, Profile $scoped)
    {
        $emailbourgeois = $action->booleanintstring('email-notify_bourgeois');
        try {
            if ($emailbourgeois == $scoped->getPref('email', 'notify_bourgeois')) {
                // No need to update setting
                return true;
            }
        } catch (NoResultException $e) {
            // Apparently there's no previously stored setting, then continue to save it as it is now.
        }

        $scoped->setPref('email', 'notify_bourgeois', $emailbourgeois);

        return true;
    }

    // Layout stuff

    public function onEndPersonalGroupNav(Menu $menu, Profile $target, Profile $scoped=null)
    {
        $menu->out->menuItem(common_local_url('showbourgeois', array('nickname' => $target->getNickname())),
                             // TRANS: Menu item in personal group navigation menu.
                             _m('MENU','Bourgeois'),
                             // @todo i18n FIXME: Need to make this two messages.
                             // TRANS: Menu item title in personal group navigation menu.
                             // TRANS: %s is a username.
                             sprintf(_('Notices marked as bourgeois by %s'), $target->getBestName()),
                             $scoped instanceof Profile && $target->id === $scoped->id && $menu->actionName =='showbourgeois',
                            'nav_timeline_bourgeois');
    }

    public function onEndPublicGroupNav(Menu $menu)
    {
        if (!common_config('singleuser', 'enabled')) {
            // TRANS: Menu item in search group navigation panel.
            $menu->out->menuItem(common_local_url('bourgeois'), _m('MENU','Bourgeois'),
                                 // TRANS: Menu item title in search group navigation panel.
                                 _('Bourgeois notices'), $menu->actionName == 'bourgeois', 'nav_timeline_markedbourgeois');
        }
    }


    protected function getActionTitle(ManagedAction $action, $verb, Notice $target, Profile $scoped)
    {
        return Bourgeois::existsForProfile($target, $scoped)
                // TRANS: Page/dialog box title when a notice is marked as bourgeois already
                ? _m('TITLE', 'Unmark notice as bourgeois')
                // TRANS: Page/dialog box title when a notice is not marked as bourgeois
                : _m('TITLE', 'Mark notice as bourgeois');
    }

    protected function doActionPreparation(ManagedAction $action, $verb, Notice $target, Profile $scoped)
    {
        if ($action->isPost()) {
            // The below tests are only for presenting to the user. POSTs which inflict
            // duplicate bourgeois entries are handled with AlreadyFulfilledException. 
            return false;
        }

        $exists = Bourgeois::existsForProfile($target, $scoped);
        $expected_verb = $exists ? 'http://activitystrea.ms/schema/1.0/unmarkbourgeois' : 'http://activitystrea.ms/schema/1.0/bourgeois';

        switch (true) {
        case $exists && ActivityUtils::compareVerbs($verb, array('http://activitystrea.ms/schema/1.0/bourgeois')):
        case !$exists && ActivityUtils::compareVerbs($verb, array('http://activitystrea.ms/schema/1.0/unmarkbourgeois')):
            common_redirect(common_local_url('activityverb',
                                array('id'   => $target->getID(),
                                      'verb' => ActivityUtils::resolveUri($expected_verb, true))));
            break;
        default:
            // No need to redirect as we are on the correct action already.
        }

        return false;
    }

    protected function doActionPost(ManagedAction $action, $verb, Notice $target, Profile $scoped)
    {
        switch (true) {
        case ActivityUtils::compareVerbs($verb, array('http://activitystrea.ms/schema/1.0/bourgeois')):
            Bourgeois::addNew($scoped, $target);
            break;
        case ActivityUtils::compareVerbs($verb, array('http://activitystrea.ms/schema/1.0/unmarkbourgeois')):
            Bourgeois::removeEntry($scoped, $target);
            break;
        default:
            throw new ServerException('ActivityVerb POST not handled by plugin that was supposed to do it.');
        }
        return false;
    }

    protected function getActivityForm(ManagedAction $action, $verb, Notice $target, Profile $scoped)
    {
        return Bourgeois::existsForProfile($target, $scoped)
                ? new UnmarkBourgeoisForm($action, $target)
                : new MarkBourgeoisForm($action, $target);
    }

    public function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'Bourgeois',
                            'version' => GNUSOCIAL_VERSION,
                            'author' => 'Hannes Mannerheim',
                            'homepage' => 'http://hannesmannerhe.im',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Bourgeois notices using ActivityStreams.'));

        return true;
    }
}

/**
 * Notify a user that one of their notices has been marked as bourgeois
 *
 * @param User    $rcpt   The user whose notice was bourgeois
 * @param Profile $sender The user who marked the notice as bourgeois
 * @param Notice  $notice The notice that was bourgeois
 *
 * @return void
 */
function mail_notify_bourgeois(User $rcpt, Profile $sender, Notice $notice)
{
    if (!$rcpt->receivesEmailNotifications() || !$rcpt->getConfigPref('email', 'notify_bourgeois')) {
        return;
    }

    if ($rcpt->hasBlocked($sender)) {
        // If the author has blocked us, don't spam them with a notification.
        return;
    }

    // We need the global mail.php for various mail related functions below.
    require_once INSTALLDIR.'/lib/mail.php';

    $bestname = $sender->getBestName();

    common_switch_locale($rcpt->language);

    // TRANS: Subject for bourgeois notification e-mail.
    // TRANS: %1$s is the adding user's long name, %2$s is the adding user's nickname.
    $subject = sprintf(_('%1$s (@%2$s) marked your notice as bourgeois'), $bestname, $sender->getNickname());

    // TRANS: Body for bourgeois notification e-mail.
    // TRANS: %1$s is the adding user's long name, $2$s is the date the notice was created,
    // TRANS: %3$s is a URL to the bourgeois notice, %4$s is the bourgeois notice text,
    // TRANS: %5$s is a URL to all bourgeois notices of the adding user, %6$s is the StatusNet sitename,
    // TRANS: %7$s is the adding user's nickname.
    $body = sprintf(_("%1\$s (@%7\$s) just marked your image from %2\$s".
                      " as bourgeois.\n\n" .
                      "The URL of your notice is:\n\n" .
                      "%3\$s\n\n" .
                      "The text of your notice is:\n\n" .
                      "%4\$s\n\n" .
                      "You can see the list of notices marked as bourgeois by %1\$s here:\n\n" .
                      "%5\$s"),
                    $bestname,
                    common_exact_date($notice->created),
                    common_local_url('shownotice',
                                     array('notice' => $notice->id)),
                    $notice->content,
                    common_local_url('showbourgeois',
                                     array('nickname' => $sender->getNickname())),
                    common_config('site', 'name'),
                    $sender->getNickname()) .
            mail_footer_block();

    $headers = _mail_prepare_headers('bourgeois', $rcpt->getNickname(), $sender->getNickname());

    common_switch_locale();
    mail_to_user($rcpt, $subject, $body, $headers);
}
