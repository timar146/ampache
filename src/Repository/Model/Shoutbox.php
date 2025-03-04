<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use PDOStatement;
use Ampache\Module\Util\Ui;

class Shoutbox
{
    public $id;
    public $object_type;
    public $object_id;
    public $user;
    public $sticky;
    public $text;
    public $data;
    public $date;

    /**
     * Constructor
     * This pulls the shoutbox information from the database and returns
     * a constructed object, uses user_shout table
     * @param integer $shout_id
     */
    public function __construct($shout_id)
    {
        // Load the data from the database
        $this->has_info($shout_id);

        return true;
    } // Constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * has_info
     * does the db call, reads from the user_shout table
     * @param integer $shout_id
     * @return boolean
     */
    private function has_info($shout_id)
    {
        $sql        = "SELECT * FROM `user_shout` WHERE `id` = ?";
        $db_results = Dba::read($sql, array($shout_id));

        $data = Dba::fetch_assoc($db_results);

        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // has_info

    /**
     * get_top
     * This returns the top user_shouts, shoutbox objects are always shown regardless and count against the total
     * number of objects shown
     * @param integer $limit
     * @param string $username
     * @return integer[]
     */
    public static function get_top($limit, $username = null)
    {
        $shouts = self::get_sticky();

        // If we've already got too many stop here
        if (count($shouts) > $limit) {
            $shouts = array_slice($shouts, 0, $limit);

            return $shouts;
        }

        // Only get as many as we need
        $limit  = (int)($limit) - count($shouts);
        $params = array();
        $sql    = "SELECT `user_shout`.`id` AS `id` FROM `user_shout` LEFT JOIN `user` ON `user`.`id` = `user_shout`.`user` WHERE `user_shout`.`sticky`='0' ";
        if ($username !== null) {
            $sql .= "AND `user`.`username` = ? ";
            $params[] = $username;
        }
        $sql .= "ORDER BY `user_shout`.`date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);

        while ($row = Dba::fetch_assoc($db_results)) {
            $shouts[] = (int)$row['id'];
        }

        return $shouts;
    } // get_top

    /**
     * get_sticky
     * This returns all current sticky shoutbox items
     */
    private static function get_sticky()
    {
        $sql = "SELECT * FROM `user_shout` WHERE `sticky`='1' ORDER BY `date` DESC";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_sticky

    /**
     * get_object
     * This takes a type and an ID and returns a created object
     * @param string $type
     * @param integer $object_id
     * @return Object
     */
    public static function get_object($type, $object_id)
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return null;
        }

        $class_name = ObjectTypeToClassNameMapper::map($type);
        $object     = new $class_name($object_id);

        if ($object->id > 0) {
            if (strtolower((string)$type) === 'song') {
                if (!$object->enabled) {
                    $object = null;
                }
            }
        } else {
            $object = null;
        }

        return $object;
    } // get_object

    /**
     * get_image
     * This returns an image tag if the type of object we're currently rolling with
     * has an image associated with it
     */
    public function get_image()
    {
        $image_string = '';
        if (Art::has_db($this->object_id, $this->object_type)) {
            $image_string = "<img class=\"shoutboximage\" height=\"75\" width=\"75\" src=\"" . AmpConfig::get('web_path') . "/image.php?object_id=" . $this->object_id . "&object_type=" . $this->object_type . "&thumb=1\" />";
        }

        return $image_string;
    } // get_image

    /**
     * create
     * This takes a key'd array of data as input and inserts a new shoutbox entry, it returns the auto_inc id
     * @param array $data
     * @return boolean|string|null
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function create(array $data)
    {
        if (!InterfaceImplementationChecker::is_library_item($data['object_type'])) {
            return false;
        }

        $sticky  = isset($data['sticky']) ? 1 : 0;
        $user    = (int)($data['user'] ?? Core::get_global('user')->id);
        $date    = (int)($data['date'] ?? time());
        $comment = strip_tags($data['comment']);

        $sql = "INSERT INTO `user_shout` (`user`, `date`, `text`, `sticky`, `object_id`, `object_type`, `data`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql,
            array($user, $date, $comment, $sticky, $data['object_id'], $data['object_type'], $data['data']));

        static::getUserActivityPoster()->post((int) $user, 'shout', $data['object_type'], (int) $data['object_id'], time());

        $insert_id = Dba::insert_id();

        // Never send email in case of user impersonation
        if (!isset($data['user']) && $insert_id !== null) {
            $class_name    = ObjectTypeToClassNameMapper::map($data['object_type']);
            $libitem       = new $class_name($data['object_id']);
            $item_owner_id = $libitem->get_user_owner();
            if ($item_owner_id) {
                if (Preference::get_by_user($item_owner_id, 'notify_email')) {
                    $item_owner = new User($item_owner_id);
                    if (!empty($item_owner->email) && Mailer::is_mail_enabled()) {
                        $libitem->format();
                        $mailer = new Mailer();
                        $mailer->set_default_sender();
                        $mailer->recipient      = $item_owner->email;
                        $mailer->recipient_name = $item_owner->fullname;
                        $mailer->subject        = T_('New shout on your content');
                        /* HINT: %1 username %2 item name being commented on */
                        $mailer->message = sprintf(T_('You just received a new shout from %1$s on your content %2$s'),
                            Core::get_global('user')->fullname, $libitem->get_fullname());
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= $comment;
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=" . $data['object_type'] . "&id=" . $data['object_id'] . "#shout" . $insert_id;
                        $mailer->send();
                    }
                }
            }
        }

        return $insert_id;
    } // create

    /**
     * update
     * This takes a key'd array of data as input and updates a shoutbox entry
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $sql = "UPDATE `user_shout` SET `text` = ?, `sticky` = ? WHERE `id` = ?";
        Dba::write($sql, array($data['comment'], (int) make_bool($data['sticky']), $this->id));

        return $this->id;
    } // create

    public function getStickyFormatted(): string
    {
        return $this->sticky == '0' ? 'No' : 'Yes';
    }

    public function getTextFormatted(): string
    {
        return preg_replace('/(\r\n|\n|\r)/', '<br />', $this->text);
    }

    public function getDateFormatted(): string
    {
        return get_datetime((int)$this->date);
    }

    /**
     * @param boolean $details
     * @param boolean $jsbuttons
     * @return string
     */
    public function get_display($details = true, $jsbuttons = false)
    {
        $object = Shoutbox::get_object($this->object_type, $this->object_id);
        $object->format();
        $img  = $this->get_image();
        $html = "<div class='shoutbox-item'>";
        $html .= "<div class='shoutbox-data'>";
        if ($details && $img) {
            $html .= "<div class='shoutbox-img'>" . $img . "</div>";
        }
        $html .= "<div class='shoutbox-info'>";
        if ($details) {
            $html .= "<div class='shoutbox-object'>" . $object->f_link . "</div>";
            $html .= "<div class='shoutbox-date'>" . get_datetime((int)$this->date) . "</div>";
        }
        $html .= "<div class='shoutbox-text'>" . $this->getTextFormatted() . "</div>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "<div class='shoutbox-footer'>";
        if ($details) {
            $html .= "<div class='shoutbox-actions'>";
            if ($jsbuttons) {
                $html .= Ajax::button('?page=stream&action=directplay&playtype=' . $this->object_type . '&' . $this->object_type . '_id=' . $this->object_id,
                    'play', T_('Play'), 'play_' . $this->object_type . '_' . $this->object_id);
                $html .= Ajax::button('?action=basket&type=' . $this->object_type . '&id=' . $this->object_id, 'add',
                    T_('Add'), 'add_' . $this->object_type . '_' . $this->object_id);
            }
            if (Access::check('interface', 25)) {
                $html .= "<a href=\"" . AmpConfig::get('web_path') . "/shout.php?action=show_add_shout&type=" . $this->object_type . "&id=" . $this->object_id . "\">" . Ui::get_icon('comment',
                        T_('Post Shout')) . "</a>";
            }
            $html .= "</div>";
        }
        $html .= "<div class='shoutbox-user'>" . T_('by') . " ";

        if ($this->user > 0) {
            $user = new User($this->user);
            $user->format();
            if ($details) {
                $html .= $user->f_link;
            } else {
                $html .= $user->username;
            }
        } else {
            $html .= T_('Guest');
        }
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `user_shout` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }
}
