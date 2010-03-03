<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2008, 2009, StatusNet, Inc.
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
 * @category Actions
 * @package  Actions
 * @author   Evan Prodromou <evan@status.net>
 * @author   Mike Cochrane <mikec@mikenz.geek.nz>
 * @author   Robin Millette <millette@controlyourself.ca>
 * @author   Adrian Lang <mail@adrianlang.de>
 * @author   Meitar Moscovitz <meitarm@gmail.com>
 * @author   Sarven Capadisli <csarven@status.net>
 * @author   Craig Andrews <candrews@integralblue.com>
 * @author   Jeffery To <jeffery.to@gmail.com>
 * @author   Zach Copley <zach@controlyourself.ca>
 * @license  GNU Affero General Public License http://www.gnu.org/licenses/
 * @link     http://status.net
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once INSTALLDIR.'/lib/personalgroupnav.php';
require_once INSTALLDIR.'/lib/noticelist.php';
require_once INSTALLDIR.'/lib/feedlist.php';

class AllAction extends ProfileAction
{
    var $notice;

    function isReadOnly($args)
    {
        return true;
    }

    function prepare($args)
    {
        parent::prepare($args);
        $cur = common_current_user();

        if (!empty($cur) && $cur->id == $this->user->id) {
            $this->notice = $this->user->noticeInbox(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);
        } else {
            $this->notice = $this->user->noticesWithFriends(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE + 1);
        }

        if ($this->page > 1 && $this->notice->N == 0) {
            $this->serverError(_('No such page'), $code = 404);
        }

        return true;
    }

    function handle($args)
    {
        parent::handle($args);

        if (!$this->user) {
            $this->clientError(_('No such user.'));
            return;
        }

        $this->showPage();
    }

    function title()
    {
        if ($this->page > 1) {
            return sprintf(_('%1$s and friends, page %2$d'), $this->user->nickname, $this->page);
        } else {
            return sprintf(_("%s and friends"), $this->user->nickname);
        }
    }

    function getFeeds()
    {
        return array(
            new Feed(Feed::RSS1,
                common_local_url(
                    'allrss', array(
                        'nickname' =>
                        $this->user->nickname)
                ),
                sprintf(_('Feed for friends of %s (RSS 1.0)'), $this->user->nickname)),
            new Feed(Feed::RSS2,
                common_local_url(
                    'ApiTimelineFriends', array(
                        'format' => 'rss',
                        'id' => $this->user->nickname
                    )
                ),
                sprintf(_('Feed for friends of %s (RSS 2.0)'), $this->user->nickname)),
            new Feed(Feed::ATOM,
                common_local_url(
                    'ApiTimelineFriends', array(
                        'format' => 'atom',
                        'id' => $this->user->nickname
                    )
                ),
                sprintf(_('Feed for friends of %s (Atom)'), $this->user->nickname))
        );
    }

    function showLocalNav()
    {
        $nav = new PersonalGroupNav($this);
        $nav->show();
    }

    function showEmptyListMessage()
    {
        $message = sprintf(_('This is the timeline for %s and friends but no one has posted anything yet.'), $this->user->nickname) . ' ';

        if (common_logged_in()) {
            $current_user = common_current_user();
            if ($this->user->id === $current_user->id) {
                $message .= _('Try subscribing to more people, [join a group](%%action.groups%%) or post something yourself.');
            } else {
                $message .= sprintf(_('You can try to [nudge %1$s](../%2$s) from his profile or [post something to his or her attention](%%%%action.newnotice%%%%?status_textarea=%3$s).'), $this->user->nickname, $this->user->nickname, '@' . $this->user->nickname);
            }
        } else {
            $message .= sprintf(_('Why not [register an account](%%%%action.register%%%%) and then nudge %s or post a notice to his or her attention.'), $this->user->nickname);
        }

        $this->elementStart('div', 'guide');
        $this->raw(common_markup_to_html($message));
        $this->elementEnd('div');
    }

    function showContent()
    {
        if (Event::handle('StartShowAllContent', array($this))) {
            $nl = new NoticeList($this->notice, $this);

            $cnt = $nl->show();

            if (0 == $cnt) {
                $this->showEmptyListMessage();
            }

            $this->pagination(
                $this->page > 1, $cnt > NOTICES_PER_PAGE,
                $this->page, 'all', array('nickname' => $this->user->nickname)
            );

            Event::handle('EndShowAllContent', array($this));
        }
    }

    function showPageTitle()
    {
        $user = common_current_user();
        if ($user && ($user->id == $this->user->id)) {
            $this->element('h1', null, _("You and friends"));
        } else {
            $this->element('h1', null, sprintf(_('%s and friends'), $this->user->nickname));
        }
    }
}
