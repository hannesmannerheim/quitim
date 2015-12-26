<?php
/**
 * Table Definition for fave
 */

class Bourgeois extends Managed_DataObject
{
    public $__table = 'bourgeois';                            // table name
    public $notice_id;                       // int(4)  primary_key not_null
    public $user_id;                         // int(4)  primary_key not_null
    public $uri;                             // varchar(191)   not 255 because utf8mb4 takes more space   not 255 because utf8mb4 takes more space
    public $created;                         // datetime  multiple_key not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice that is bourgeois'),
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'user who thinks this notice is bourgeois'),
                'uri' => array('type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier, usually a tag URI'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_id', 'user_id'),
            'unique keys' => array(
                'bourgeois_uri_key' => array('uri'),
            ),
            'foreign keys' => array(
                'bourgeois_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
                'bourgeois_user_id_fkey' => array('profile', array('user_id' => 'id')), // note: formerly referenced notice.id, but we can now record remote users' favorites
            ),
            'indexes' => array(
                'bourgeois_notice_id_idx' => array('notice_id'),
                'bourgeois_user_id_idx' => array('user_id', 'modified'),
                'bourgeois_modified_idx' => array('modified'),
            ),
        );
    }

    /**
     * Save a favorite record.
     * @fixme post-author notification should be moved here
     *
     * @param Profile $actor  the local or remote Profile who favorites
     * @param Notice  $target the notice that is favorited
     * @return Fave record on success
     * @throws Exception on failure
     */
    static function addNew(Profile $actor, Notice $target) {
        if (self::existsForProfile($target, $actor)) {
            // TRANS: Client error displayed when trying to mark a notice as favorite that already is a favorite.
            throw new AlreadyFulfilledException(_('You have already marked this as bourgeois!'));
        }

        $act = new Activity();
        $act->type    = ActivityObject::ACTIVITY;
        $act->verb    = 'http://activitystrea.ms/schema/1.0/bourgeois';
        $act->time    = time();
        $act->id      = self::newUri($actor, $target, common_sql_date($act->time));
        $act->title   = _("Mark as bourgeois");
        // TRANS: Message that is the "content" of a favorite (%1$s is the actor's nickname, %2$ is the favorited
        //        notice's nickname and %3$s is the content of the favorited notice.)
        $act->content = sprintf(_('%1$s marked something by %2$s as bourgeois: %3$s'),
                                $actor->getNickname(), $target->getProfile()->getNickname(),
                                $target->rendered ?: $target->content);
        $act->actor   = $actor->asActivityObject();
        $act->target  = $target->asActivityObject();
        $act->objects = array(clone($act->target));

        // saveActivity will in turn also call Fave::saveActivityObject which does
        // what this function used to do before this commit.
        $stored = Notice::saveActivity($act, $actor);

        return $stored;
    }

    public function removeEntry(Profile $actor, Notice $target)
    {
        $bourgeois            = new Bourgeois();
        $bourgeois->user_id   = $actor->getID();
        $bourgeois->notice_id = $target->getID();
        if (!$bourgeois->find(true)) {
            // TRANS: Client error displayed when trying to remove a 'favor' when there is none in the first place.
            throw new AlreadyFulfilledException(_('This is already not marked as bourgeois.'));
        }

        $result = $bourgeois->delete();
        if ($result === false) {
            common_log_db_error($bourgeois, 'DELETE', __FILE__);
            // TRANS: Server error displayed when removing a favorite from the database fails.
            throw new ServerException(_('Could not unmark as bourgeois.'));
        }

        Bourgeois::blowCacheForProfileId($actor->getID());
        Bourgeois::blowCacheForNoticeId($target->getID());
    }

    // exception throwing takeover!
    public function insert()
    {
        if (parent::insert()===false) {
            common_log_db_error($this, 'INSERT', __FILE__);
            throw new ServerException(sprintf(_m('Could not store new object of type %s'), get_called_class()));
        }
        self::blowCacheForProfileId($this->user_id);
        self::blowCacheForNoticeId($this->notice_id);
        return $this;
    }

    public function delete($useWhere=false)
    {
        $profile = Profile::getKV('id', $this->user_id);
        $notice  = Notice::getKV('id', $this->notice_id);

        $result = null;

        if (Event::handle('StartUnmarkBourgeoisNotice', array($profile, $notice, &$result))) {

            $result = parent::delete($useWhere);

            self::blowCacheForProfileId($this->user_id);
            self::blowCacheForNoticeId($this->notice_id);
            self::blow('popular');

            if ($result) {
                Event::handle('EndUnmarkBourgeoisNotice', array($profile, $notice));
            }
        }

        return $result;
    }

    static function stream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $own=false, $since_id=0, $max_id=0)
    {
        $stream = new BourgeoisNoticeStream($user_id, $own);

        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }

    function idStream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $own=false, $since_id=0, $max_id=0)
    {
        $stream = new BourgeoisNoticeStream($user_id, $own);

        return $stream->getNoticeIds($offset, $limit, $since_id, $max_id);
    }

    function asActivity()
    {
        $target = $this->getTarget();
        $actor  = $this->getActor();

        $act = new Activity();

        $act->verb = 'http://activitystrea.ms/schema/1.0/bourgeois';

        // FIXME: rationalize this with URL below

        $act->id   = $this->getUri();

        $act->time    = strtotime($this->created);
        // TRANS: Activity title when marking a notice as bourgeois.
        $act->title   = _("Mark as bourgeois");
        // TRANS: Message that is the "content" of a favorite (%1$s is the actor's nickname, %2$ is the favorited
        //        notice's nickname and %3$s is the content of the favorited notice.)
        $act->content = sprintf(_('%1$s marked something by %2$s as bourgeois: %3$s'),
                                $actor->getNickname(), $target->getProfile()->getNickname(),
                                $target->rendered ?: $target->content);

        $act->actor     = $actor->asActivityObject();
        $act->target    = $target->asActivityObject();
        $act->objects   = array(clone($act->target));

        return $act;
    }

    static function existsForProfile($notice, Profile $scoped)
    {
        $bourgeois = self::pkeyGet(array('user_id'=>$scoped->id, 'notice_id'=>$notice->id));

        return ($bourgeois instanceof Bourgeois);
    }

    /**
     * Fetch a stream of favorites by profile
     *
     * @param integer $profileId Profile that faved
     * @param integer $offset    Offset from last
     * @param integer $limit     Number to get
     *
     * @return mixed stream of faves, use fetch() to iterate
     *
     * @todo Cache results
     * @todo integrate with Fave::stream()
     */

    static function byProfile($profileId, $offset, $limit)
    {
        $bourgeois = new Bourgeois();

        $bourgeois->user_id = $profileId;

        $bourgeois->orderBy('modified DESC');

        $bourgeois->limit($offset, $limit);

        $bourgeois->find();

        return $bourgeois;
    }

    static function countByProfile(Profile $profile)
    {
        $c = Cache::instance();
        if (!empty($c)) {
            $cnt = $c->get(Cache::key('bourgeois:count_by_profile:'.$profile->id));
            if (is_integer($cnt)) {
                return $cnt;
            }
        }

        $bourgeois = new Bourgeois();
        $bourgeois->user_id = $profile->id;
        $cnt = (int) $bourgeois->count('notice_id');

        if (!empty($c)) {
            $c->set(Cache::key('bourgeois:count_by_profile:'.$profile->id), $cnt);
        }

        return $cnt;
    }

    static protected $_bourgeois = array();

    /**
     * All faves of this notice
     *
     * @param Notice $notice A notice we wish to get faves for (may still be ArrayWrapper)
     *
     * @return array Array of Fave objects
     */
    static public function byNotice($notice)
    {
        if (!isset(self::$_bourgeois[$notice->id])) {
            self::fillBourgeois(array($notice->id));
        }
        return self::$_bourgeois[$notice->id];
    }

    static public function fillBourgeois(array $notice_ids)
    {
        $bourgeoisMap = Bourgeois::listGet('notice_id', $notice_ids);
        self::$_bourgeois = array_replace(self::$_bourgeois, $bourgeoisMap);
    }

    static public function blowCacheForProfileId($profile_id)
    {
        $cache = Cache::instance();
        if ($cache) {
            // Faves don't happen chronologically, so we need to blow
            // ;last cache, too
            $cache->delete(Cache::key('bourgeois:ids_by_user:'.$profile_id));
            $cache->delete(Cache::key('bourgeois:ids_by_user:'.$profile_id.';last'));
            $cache->delete(Cache::key('bourgeois:ids_by_user_own:'.$profile_id));
            $cache->delete(Cache::key('bourgeois:ids_by_user_own:'.$profile_id.';last'));
            $cache->delete(Cache::key('bourgeois:count_by_profile:'.$profile_id));
        }
    }
    static public function blowCacheForNoticeId($notice_id)
    {
        $cache = Cache::instance();
        if ($cache) {
            $cache->delete(Cache::key('bourgeois:list-ids:notice_id:'.$notice_id));
        }
    }

    // Remember that we want the _activity_ notice here, not faves applied
    // to the supplied Notice (as with byNotice)!
    static public function fromStored(Notice $stored)
    {
        $class = get_called_class();
        $object = new $class;
        $object->uri = $stored->uri;
        if (!$object->find(true)) {
            throw new NoResultException($object);
        }
        return $object;
    }

    /**
     * Retrieves the _targeted_ notice of a verb (such as the notice that was
     * _favorited_, but not the favorite activity itself).
     *
     * @param Notice $stored    The activity notice.
     *
     * @throws NoResultException when it can't find what it's looking for.
     */
    static public function getTargetFromStored(Notice $stored)
    {
        return self::fromStored($stored)->getTarget();
    }

    static public function getObjectType()
    {
        return 'activity';
    }

    public function asActivityObject(Profile $scoped=null)
    {
        $actobj = new ActivityObject();
        $actobj->id = $this->getUri();
        $actobj->type = ActivityUtils::resolveUri(self::getObjectType());
        $actobj->actor = $this->getActorObject();
        $actobj->target = $this->getTargetObject();
        $actobj->objects = array(clone($actobj->target));
        $actobj->verb = 'http://activitystrea.ms/schema/1.0/bourgeois';
        $actobj->title = ActivityUtils::verbToTitle($actobj->verb);
        $actobj->content = $this->getTarget()->rendered ?: $this->getTarget()->content;
        return $actobj;
    }

    /**
     * @param ActivityObject $actobj The _favored_ notice (which we're "in-reply-to")
     * @param Notice         $stored The _activity_ notice, i.e. the favor itself.
     */
    static public function parseActivityObject(ActivityObject $actobj, Notice $stored)
    {
        $local = ActivityUtils::findLocalObject($actobj->getIdentifiers());
        if (!$local instanceof Notice) {
            // $local always returns something, but this was not what we expected. Something is wrong.
            throw new Exception('Something other than a Notice was returned from findLocalObject');
        }
 
        $actor = $stored->getProfile();
        $object = new Bourgeois();
        $object->user_id = $stored->getProfile()->id;
        $object->notice_id = $local->id;
        $object->uri = $stored->uri;
        $object->created = $stored->created;
        $object->modified = $stored->modified;
        return $object;
    }

    static public function extendActivity(Notice $stored, Activity $act, Profile $scoped=null)
    {
        $target = self::getTargetFromStored($stored);

        // The following logic was copied from StatusNet's Activity plugin
        if (ActivityUtils::compareVerbs($target->verb, array(ActivityVerb::POST))) {
            // "I like the thing you posted"
            $act->objects = $target->asActivity()->objects;
        } else {
            // "I like that you did whatever you did"
            $act->target = $target->asActivityObject();
            $act->objects = array(clone($act->target));
        }
        $act->context->replyToID = $target->getUri();
        $act->context->replyToUrl = $target->getUrl();
        $act->title = ActivityUtils::verbToTitle($act->verb);
    }

    static function saveActivityObject(ActivityObject $actobj, Notice $stored)
    {
        $object = self::parseActivityObject($actobj, $stored);
        $object->insert();  // exception throwing in Fave's case!

        self::blowCacheForProfileId($object->user_id);
        self::blowCacheForNoticeId($object->notice_id);
        self::blow('bourgeois');

        Event::handle('EndMarkBourgeoisNotice', array($stored->getProfile(), $object->getTarget()));
        return $object;
    }

    public function getAttentionArray() {
        // not all objects can/should carry attentions, so we don't require extending this
        // the format should be an array with URIs to mentioned profiles
        return array();
    }

    public function getTarget()
    {
        // throws exception on failure
        $target = new Notice();
        $target->id = $this->notice_id;
        if (!$target->find(true)) {
            throw new NoResultException($target);
        }

        return $target;
    }

    public function getTargetObject()
    {
        return $this->getTarget()->asActivityObject();
    }

    protected $_stored = array();

    public function getStored()
    {
        if (!isset($this->_stored[$this->uri])) {
            $stored = new Notice();
            $stored->uri = $this->uri;
            if (!$stored->find(true)) {
                throw new NoResultException($stored);
            }
            $this->_stored[$this->uri] = $stored;
        }
        return $this->_stored[$this->uri];
    }

    public function getActor()
    {
        return Profile::getByID($this->user_id);
    }

    public function getActorObject()
    {
        return $this->getActor()->asActivityObject();
    }

    public function getUri()
    {
        if (!empty($this->uri)) {
            return $this->uri;
        }

        // We (should've in this case) created it ourselves, so we tag it ourselves
        return self::newUri($this->getActor(), $this->getTarget(), $this->created);
    }

    static function newUri(Profile $actor, Managed_DataObject $target, $created=null)
    {
        if (is_null($created)) {
            $created = common_sql_now();
        }
        return TagURI::mint(strtolower(get_called_class()).':%d:%s:%d:%s',
                                        $actor->id,
                                        ActivityUtils::resolveUri(self::getObjectType(), true),
                                        $target->id,
                                        common_date_iso8601($created));
    }
}
