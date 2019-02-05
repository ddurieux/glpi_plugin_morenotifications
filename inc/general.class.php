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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMorenotificationsGeneral extends PluginMorenotificationsEntity {

   /**
   * Get name of this type
   *
   *@return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb = 0) {
      return __('More notifications', 'morenotifications');
   }



   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    **/
   static function cronMorenotifications($task = null) {
      global $DB,$CFG_GLPI;

      if (!$CFG_GLPI["use_notifications"]) {
         return 0;
      }

      $CronTask = new CronTask();
      $alert    = new Alert();
      $pmEntity = new PluginMorenotificationsEntity();

      if ($CronTask->getFromDBbyName("PluginMorenotificationsGeneral", "morenotifications")) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }
      $message = [];
      $cron_status = 0;

      // For Event ticketnotclosed
      foreach (Notification::getNotificationsByEventAndType(
                          'ticketnotclosed',
                          'PluginMorenotificationsGeneral', 0) as $dataNotif) {
         $entity = 0;
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`is_deleted` = 0
                         AND `glpi_tickets`.`status` IN ('".CommonITILObject::INCOMING."',
                                                         '".CommonITILObject::ASSIGNED."',
                                                         '".CommonITILObject::PLANNED."',
                                                         '".CommonITILObject::WAITING."')
                         AND `glpi_tickets`.`closedate` IS NULL
                         AND `glpi_tickets`.`due_date` < NOW()";
         $tickets = [];
         $tot = 0;
         $days = $pmEntity->getValueAncestor("ticketnotclosed", $entity);
         if ($days > 0) {
            foreach ($DB->request($query) as $tick) {
               $lastAlert = $alert->getAlertDate('Ticket',
                                                 $tick['id'],
                                                 $dataNotif['id']);
               $send = true;
               if ($lastAlert) {
                  $date_time = explode(' ', $lastAlert);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - ($days * 24 * 3600))) {
                     $send = false;
                  }
               } else {
                  // In case ticket late but not have yet send notification
                  // so send only the next day
                  $date_time = explode(' ', $tick['due_date']);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - (24 * 3600))) {
                     $send = false;
                  }
               }

               if ($send) {
                  if (NotificationEvent::raiseEvent('ticketnotclosed', new PluginMorenotificationsGeneral(),
                                                    ['item'        => $tick,
                                                     'entities_id' => $entity])) {
                     $tot += count($tickets);
                     $task->addVolume(count($tickets));
                     $task->log(sprintf(__('%1$s: %2$s'),
                                        Dropdown::getDropdownName('glpi_entities', $entity),
                                        count($tickets)));
                     $input = [
                         'itemtype' => 'Ticket',
                         'items_id' => $tick['id'],
                         'type'     => $dataNotif['id'],
                         'date'     => date('Y-m-d H:i:s')
                     ];
                     $alert->add($input);
                  }
               }
            }
         }
      }

      // For Event ticketwaiting
      $dataNotifs = Notification::getNotificationsByEventAndType(
                          'ticketwaiting',
                          'PluginMorenotificationsGeneral',
                          0);
      foreach ($dataNotifs as $dataNotif) {
         $entity = 0;
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`is_deleted` = 0
                         AND `glpi_tickets`.`status` = '".CommonITILObject::WAITING."'";
         $tickets = [];
         $tot = 0;
         $days = $pmEntity->getValueAncestor("ticketwaiting", $entity);
         if ($days > 0) {
            foreach ($DB->request($query) as $tick) {
               $lastAlert = $alert->getAlertDate('Ticket',
                                                 $tick['id'],
                                                 $dataNotif['id']);
               $send = true;
               if ($lastAlert) {
                  $date_time = explode(' ', $lastAlert);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - ($days * 24 * 3600))) {
                     $send = false;
                  }
               } else {
                  // In case ticket late but not have yet send notification
                  // so send only the next day
                  $date_time = explode(' ', $tick['begin_waiting_date']);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - (24 * 3600))) {
                     $send = false;
                  }
               }

               if ($send) {
                  if (NotificationEvent::raiseEvent('ticketwaiting', new PluginMorenotificationsGeneral(),
                                                    ['item'        => $tick,
                                                     'entities_id' => $entity])) {
                     $tot += count($tickets);
                     $task->addVolume(count($tickets));
                     $task->log(sprintf(__('%1$s: %2$s'),
                                        Dropdown::getDropdownName('glpi_entities', $entity),
                                        count($tickets)));
                     $input = [
                         'itemtype' => 'Ticket',
                         'items_id' => $tick['id'],
                         'type'     => $dataNotif['id'],
                         'date'     => date('Y-m-d H:i:s')
                     ];
                     $alert->add($input);
                  }
               }
            }
         }
      }
      return $cron_status;
   }
}
