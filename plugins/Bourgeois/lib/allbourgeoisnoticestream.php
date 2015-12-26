<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Stream of notices sorted by popularity
 * 
 * PHP version 5
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
 *
 * @category  Popular
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Stream of notices sorted by popularity
 *
 * @category  Popular
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class AllBourgeoisNoticeStream extends ScopingNoticeStream
{
    function __construct($profile=null)
    {
        parent::__construct(new CachingNoticeStream(new RawAllBourgeoisNoticeStream(),
                                                    'allbourgeois',
                                                    false),
                            $profile);
    }
}

class RawAllBourgeoisNoticeStream extends NoticeStream
{
    function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $weightexpr = common_sql_weight('modified', common_config('popular', 'dropoff'));
        $cutoff = sprintf("modified > '%s'",
                          common_sql_date(time() - common_config('popular', 'cutoff')));

        $bourgeois = new Bourgeois();
        $bourgeois->selectAdd();
        $bourgeois->selectAdd('notice_id');
        $bourgeois->selectAdd("$weightexpr as weight");
        $bourgeois->whereAdd($cutoff);
        $bourgeois->orderBy('weight DESC');
        $bourgeois->groupBy('notice_id');

        if (!is_null($offset)) {
            $bourgeois->limit($offset, $limit);
        }

        // FIXME: $since_id, $max_id are ignored

        $ids = array();

        if ($bourgeois->find()) {
            while ($bourgeois->fetch()) {
                $ids[] = $bourgeois->notice_id;
            }
        }

        return $ids;
    }
}

