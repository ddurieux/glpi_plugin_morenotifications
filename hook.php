<?php

/*
   ------------------------------------------------------------------------
   Plugin Morenotifications for GLPI
   Copyright (C) 2014-2019 by the Plugin Morenotifications for David Durieux.

   https://github.com/ddurieux/glpi_plugin_morenotifications
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Plugin Morenotifications project.

   Plugin Morenotifications for GLPI is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Plugin Morenotifications for GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Morenotifications. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Plugin Morenotifications for GLPI
   @author    David Durieux
   @co-author
   @comment
   @copyright Copyright (c) 2011-2019 Plugin Morenotifications for David Durieux
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/glpi_plugin_morenotifications
   @since     2014

   ------------------------------------------------------------------------
 */

function plugin_morenotifications_install() {
   global $DB;

   if (!$DB->tableExists('glpi_plugin_morenotifications_entities')) {
      $query = "CREATE TABLE `glpi_plugin_morenotifications_entities` (
         `id` int(11) NOT NULL auto_increment,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `ticketnotclosed` int(2) NOT NULL DEFAULT '0',
         `ticketwaiting` int(2) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
      ";
      $DB->query($query);
      $query = "INSERT INTO `glpi_plugin_morenotifications_entities`"
              . " (`entities_id`) VALUES ('0')";
      $DB->query($query);
   }

   CronTask::Register('PluginMorenotificationsGeneral',
                      'morenotifications',
                      '86400',
                      ['mode' => 2,
                       'allowmode' => 3,
                       'logs_lifetime'=> 30]);
   return true;
}



// Uninstall process for plugin : need to return true if succeeded
function plugin_morenotifications_uninstall() {
   global $DB;

   if ($DB->tableExists('glpi_plugin_morenotifications_entities')) {
      $DB->query("DROP TABLE `glpi_plugin_morenotifications_entities`");
   }

   CronTask::Unregister('morenotifications');

   return true;
}


function plugin_morenotifications_notiftag(NotificationTarget $item) {
   global $DB;

   $entity = new Entity();
   $notepad = new Notepad();

   $entity->getFromDB($item->entity);
   $notealls = $notepad->getAllForItem($entity);
   $notes = [];
   foreach ($notealls as $noteall) {
      $notes[] = $noteall['content'];
   }

   $item->datas['##entity.notes##'] = implode("\n", $notes);

   $item->datas['techs'] = [];
   if ($item->obj->countUsers(CommonITILActor::ASSIGN)) {
      foreach ($item->obj->getUsers(CommonITILActor::ASSIGN) as $tmpusr) {
         $uid = $tmpusr['users_id'];
         $user_tmp = new User();
         if ($uid
             && $user_tmp->getFromDB($uid)) {
            $tech = [
               '##techs.name##'   => $user_tmp->getName(),
               '##techs.phone##'  => $user_tmp->fields['phone'],
               '##techs.mobile##' => $user_tmp->fields['mobile'],
               '##techs.email##'  => implode(' ,', UserEmail::getAllForUser($uid))
            ];
            $item->datas['techs'][] = $tech;
         }
      }
   }

   // **************** Get entity calendar of the ticket **************** //
   $query = "SELECT *
      FROM `glpi_calendarsegments`
      WHERE `calendars_id` = '".$entity->fields['calendars_id']."'
      ORDER BY `day`, `begin`, `end`";
   $result = $DB->query($query);
   $numrows = $DB->numrows($result);

   $cal_data = __('Day').", ".__('Start').", ".__('End')."\n";
   $daysofweek = Toolbox::getDaysOfWeekArray();

   if ($numrows) {
      while ($data = $DB->fetch_assoc($result)) {
         $cal_data .= $daysofweek[$data['day']].", ".$data["begin"].", ".$data["end"]."\n";
      }
   }

   $item->datas["##ticket.calendardetails##"] = $cal_data;
}
