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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMorenotificationsGeneral extends CommonDBTM {

   /**
   * Get name of this type
   *
   *@return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return __('More notifications', 'morenotifications');
   }



   /**
    * Cron action
    *
    * @param $task for log, if NULL display
    *
    **/
   static function cronMorenotifications($task=NULL) {
      global $DB,$CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $CronTask = new CronTask();
      $alert    = new Alert();
      $pmEntity = new PluginMorenotificationsEntity();

      if ($CronTask->getFromDBbyName("PluginMorenotificationsGeneral", "morenotifications")) {
         if ($CronTask->fields["state"]==CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }
      $message=array();
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
         $tickets = array();
         $tot = 0;
         $days = $pmEntity->getValueAncestor("ticketnotclosed", $entity);
         if ($days > 0) {
            foreach ($DB->request($query) as $tick) {
               $lastAlert = $alert->getAlertDate('Ticket',
                                                 $tick['id'],
                                                 $dataNotif['id']);
               $send = True;
               if ($lastAlert) {
                  $date_time = explode(' ', $lastAlert);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - ($days * 24 * 3600))) {
                     $send = False;
                  }
               } else {
                  // In case ticket late but not have yet send notification
                  // so send only the next day
                  $date_time = explode(' ', $tick['due_date']);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - (24 * 3600))) {
                     $send = False;
                  }
               }

               if ($send) {
                  if (NotificationEvent::raiseEvent('ticketnotclosed', new PluginMorenotificationsGeneral(),
                                                    array('item'        => $tick,
                                                          'entities_id' => $entity))) {
                     $tot += count($tickets);
                     $task->addVolume(count($tickets));
                     $task->log(sprintf(__('%1$s: %2$s'),
                                        Dropdown::getDropdownName('glpi_entities', $entity),
                                        count($tickets)));
                     $input = array(
                         'itemtype' => 'Ticket',
                         'items_id' => $tick['id'],
                         'type'     => $dataNotif['id'],
                         'date'     => date('Y-m-d H:i:s')
                     );
                     $alert->add($input);
                  }
               }
            }
         }
      }


      // For Event ticketwaiting
      foreach (Notification::getNotificationsByEventAndType(
                          'ticketwaiting',
                          'PluginMorenotificationsGeneral',
                          0) as $dataNotif ) {
         $entity = 0;
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`is_deleted` = 0
                         AND `glpi_tickets`.`status` = '".CommonITILObject::WAITING."'";
         $tickets = array();
         $tot = 0;
         $days = $pmEntity->getValueAncestor("ticketwaiting", $entity);
         if ($days > 0) {
            foreach ($DB->request($query) as $tick) {
               $lastAlert = $alert->getAlertDate('Ticket',
                                                 $tick['id'],
                                                 $dataNotif['id']);
               $send = True;
               if ($lastAlert) {
                  $date_time = explode(' ', $lastAlert);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - ($days * 24 * 3600))) {
                     $send = False;
                  }
               } else {
                  // In case ticket late but not have yet send notification
                  // so send only the next day
                  $date_time = explode(' ', $tick['begin_waiting_date']);
                  if (strtotime($date_time[0]) > (strtotime(date("Y-m-d")) - (24 * 3600))) {
                     $send = False;
                  }
               }

               if ($send) {
                  if (NotificationEvent::raiseEvent('ticketwaiting', new PluginMorenotificationsGeneral(),
                                                    array('item'        => $tick,
                                                          'entities_id' => $entity))) {
                     $tot += count($tickets);
                     $task->addVolume(count($tickets));
                     $task->log(sprintf(__('%1$s: %2$s'),
                                        Dropdown::getDropdownName('glpi_entities', $entity),
                                        count($tickets)));
                     $input = array(
                         'itemtype' => 'Ticket',
                         'items_id' => $tick['id'],
                         'type'     => $dataNotif['id'],
                         'date'     => date('Y-m-d H:i:s')
                     );
                     $alert->add($input);
                  }
               }
            }
         }
      }


      return $cron_status;
   }


}

?>