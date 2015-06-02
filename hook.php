<?php

/*
   ------------------------------------------------------------------------
   Plugin Morenotifications for GLPI
   Copyright (C) 2014-2014 by the Plugin Morenotifications for David Durieux.

   https://
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
   @copyright Copyright (c) 2011-2014 Plugin Morenotifications for David Durieux
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://
   @since     2014

   ------------------------------------------------------------------------
 */

function plugin_morenotifications_install() {
   global $DB;

   if (!TableExists('glpi_plugin_morenotifications_entities')) {
      $query = "CREATE TABLE `glpi_plugin_morenotifications_entities` (
         `id` int(11) NOT NULL auto_increment,
         `entities_id` int(11) NOT NULL DEFAULT '0',
         `ticketnotclosed` int(2) NOT NULL DEFAULT '0',
         `ticketwaiting` int(2) NOT NULL DEFAULT '0',
         PRIMARY KEY (`id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
      ";
      $DB->query($query);
      $query = "INSERT INTO `glpi_plugin_morenotifications_entities`"
              . " (`entities_id`) VALUES ('0')";
      $DB->query($query);
   }

   CronTask::Register('PluginMorenotificationsGeneral',
                      'morenotifications',
                      '86400',
                      array('mode' => 2,
                            'allowmode' => 3,
                            'logs_lifetime'=> 30));

   return true;
}



// Uninstall process for plugin : need to return true if succeeded
function plugin_morenotifications_uninstall() {
   global $DB;

   if (TableExists('glpi_plugin_morenotifications_entities')) {
      $DB->query("DROP TABLE `glpi_plugin_morenotifications_entities`");
   }

   CronTask::Unregister('morenotifications');

   return true;
}


function plugin_morenotifications_notiftag(NotificationTarget $item) {

   $entity = new Entity();
   $entity->getFromDB($item->entity);

   $item->datas['##entity.phonenumber##'] = $entity->fields['phonenumber'];
   $item->datas['##entity.address##']     = $entity->fields['address'];
   $item->datas['##entity.fax##']         = $entity->fields['fax'];
   $item->datas['##entity.website##']     = $entity->fields['website'];
   $item->datas['##entity.email##']       = $entity->fields['email'];
   $item->datas['##entity.postcode##']    = $entity->fields['postcode'];
   $item->datas['##entity.town##']        = $entity->fields['town'];
   $item->datas['##entity.state##']       = $entity->fields['state'];
   $item->datas['##entity.country##']     = $entity->fields['country'];
   $item->datas['##entity.notepad##']     = $entity->fields['notepad'];


   if (isset($item->obj->fields['itemtype'])) {
      $user = new User();
      $i = new $item->obj->fields['itemtype'];
      $i->getFromDB($item->obj->fields['items_id']);
      if ($i->fields['users_id_tech'] > 0) {
         if ($user->getFromDB($i->fields['users_id_tech'])) {

            $item->datas['##ticket.item.tech.name##']   = $user->fields['name'];
            $item->datas['##ticket.item.tech.phone##']  = $user->fields['phone'];
            $item->datas['##ticket.item.tech.mobile##'] = $user->fields['mobile'];
            $item->datas['##ticket.item.tech.email##']  = implode(' ,', UserEmail::getAllForUser($i->fields['users_id_tech']));
         }
      }


   }
}

?>