<?php
/**
 * Table Definition for reply
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class QuitimNotification extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'quitimnotification';  // table name
    public $id;                              // int(4)  primary_key not_null
    public $to_profile_id;                   // int(4)  primary_key not_null
    public $from_profile_id;                 // int(4)  primary_key not_null    
    public $type;                            // varchar(7)
    public $first_notice_id_in_conversation; // int(4)  primary_key not_null
    public $notice_id;                       // int(4)  primary_key not_null    
    public $date;                            // datetime  multiple_key not_null default_0000-00-00%2000%3A00%3A00 

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'serial', 'not null' => true),
                'to_profile_id' => array('type' => 'int', 'description' => 'the profile being notified'),
                'from_profile_id' => array('type' => 'int', 'description' => 'the profile that is notifying'),                
                'ntype' => array('type' => 'varchar', 'length' => 7, 'description' => 'reply, like, mention or follow'),
                'first_notice_id_in_conversation' => array('type' => 'int', 'description' => 'the conversation starter, e.i. the image'),
                'notice_id' => array('type' => 'int', 'description' => 'id for the reply or mention or notice being faved'),
                'is_seen' => array('type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'if the notification has been seen'),                
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created')
            ),
            'primary key' => array('id')
        );
    }    
	
    /**
     * Wrapper for record insertion to update related caches
     */
    function insert()
    {
        $result = parent::insert();

        if ($result) {
            self::blow('quitimnotification:stream:%d', $this->profile_id);
        }

        return $result;
    }
}
