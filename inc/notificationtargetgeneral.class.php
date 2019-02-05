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

class PluginMorenotificationsNotificationTargetGeneral extends NotificationTargetTicket {


   /**
    * @param $entity          (default '')
    * @param $event           (default '')
    * @param $object          (default null)
    * @param $options   array
   **/
   function __construct($entity = '', $event = '', $object = null, $options = []) {

      parent::__construct($entity, $event, $object, $options);

      if (isset($options['item'])) {
         $ticket = new Ticket();
         $ticket->getFromDB($options['item']['id']);
         $this->obj = $ticket;
      }
   }



   function getEvents() {
      return ['ticketnotclosed' => __('Ticket not solved (use due date)', 'morenotifications'),
              'ticketwaiting'   => __('Ticket waiting', 'morenotifications')];
   }



   /**
    * Get additionnals targets for ITIL objects
    *
    * @param $event  (default '')
   **/
   function getAdditionalTargets($event = '') {

      if ($event == 'ticketnotclosed'
              || $event == 'ticketwaiting') {

         $this->target = [];
         $this->addTarget(Notification::AUTHOR, __('Requester'));
         $this->addTarget(Notification::RECIPIENT, __('Writer'));
         $this->addTarget(Notification::REQUESTER_GROUP_WITHOUT_SUPERVISOR,
                 __('Requester group without manager'));
         $this->addTarget(Notification::REQUESTER_GROUP, __('Requester group'));

         $this->addTarget(Notification::ASSIGN_TECH, __('Technician in charge of the ticket'));
         $this->addTarget(Notification::ASSIGN_GROUP_WITHOUT_SUPERVISOR,
                          __('Group in charge of the ticket without manager'));
         $this->addTarget(Notification::ASSIGN_GROUP, __('Group in charge of the ticket'));
      }
   }



   /**
    * Get all data needed for template processing
    *
    * @param $event
    * @param $options array
   **/
   function getDatasForTemplate($event, $options = []) {
      global $CFG_GLPI;

      // Get datas from ITIL objects
      $this->datas = $this->getDatasForObject($this->obj, $options);
   }


   function getDataForObject(CommonDBTM $item, array $options, $simple = false) {
      global $CFG_GLPI, $DB;

      $objettype = strtolower($item->getType());

      $datas["##$objettype.title##"]        = $item->getField('name');
      $datas["##$objettype.content##"]      = $item->getField('content');
      $datas["##$objettype.description##"]  = $item->getField('content');
      $datas["##$objettype.id##"]           = sprintf("%07d", $item->getField("id"));

      $datas["##$objettype.url##"]
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              $objettype."_".$item->getField("id"));

      $datas["##$objettype.urlapprove##"]
                           = $this->formatURL($options['additionnaloption']['usertype'],
                                              $objettype."_".$item->getField("id")."_".
                                                        $item->getType().'$2');

      $entity = new Entity();
      if ($entity->getFromDB($this->getEntity())) {
         $datas["##$objettype.entity##"]      = $entity->getField('completename');
         $datas["##$objettype.shortentity##"] = $entity->getField('name');
      }

      $datas["##$objettype.storestatus##"]  = $item->getField('status');
      $datas["##$objettype.status##"]       = $item->getStatus($item->getField('status'));

      $datas["##$objettype.urgency##"]      = $item->getUrgencyName($item->getField('urgency'));
      $datas["##$objettype.impact##"]       = $item->getImpactName($item->getField('impact'));
      $datas["##$objettype.priority##"]     = $item->getPriorityName($item->getField('priority'));
      $datas["##$objettype.time##"]         = $item->getActionTime($item->getField('actiontime'));

      $datas["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
      $datas["##$objettype.closedate##"]    = Html::convDateTime($item->getField('closedate'));
      $datas["##$objettype.solvedate##"]    = Html::convDateTime($item->getField('solvedate'));
      $datas["##$objettype.duedate##"]      = Html::convDateTime($item->getField('due_date'));

      $datas["##$objettype.category##"] = '';
      if ($item->getField('itilcategories_id')) {
         $datas["##$objettype.category##"]
                              = Dropdown::getDropdownName('glpi_itilcategories',
                                                          $item->getField('itilcategories_id'));
      }

      $datas["##$objettype.authors##"] = '';
      $datas['authors'] = [];
      if ($item->countUsers(CommonITILActor::REQUESTER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
            $uid = $tmpusr['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();

               $tmp = [];
               $tmp['##author.id##']   = $uid;
               $tmp['##author.name##'] = $user_tmp->getName();

               if ($user_tmp->getField('locations_id')) {
                  $tmp['##author.location##']
                                    = Dropdown::getDropdownName('glpi_locations',
                                                                $user_tmp->getField('locations_id'));
               } else {
                  $tmp['##author.location##'] = '';
               }

               if ($user_tmp->getField('usertitles_id')) {
                  $tmp['##author.title##']
                                    = Dropdown::getDropdownName('glpi_usertitles',
                                                                $user_tmp->getField('usertitles_id'));
               } else {
                  $tmp['##author.title##'] = '';
               }

               if ($user_tmp->getField('usercategories_id')) {
                  $tmp['##author.category##']
                                    = Dropdown::getDropdownName('glpi_usercategories',
                                                                $user_tmp->getField('usercategories_id'));
               } else {
                  $tmp['##author.category##'] = '';
               }

               $tmp['##author.email##']  = $user_tmp->getDefaultEmail();
               $tmp['##author.mobile##'] = $user_tmp->getField('mobile');
               $tmp['##author.phone##']  = $user_tmp->getField('phone');
               $tmp['##author.phone2##'] = $user_tmp->getField('phone2');
               $datas['authors'][]       = $tmp;
            } else {
               // Anonymous users only in xxx.authors, not in authors
               $users[] = $tmpusr['alternative_email'];
            }
         }
         $datas["##$objettype.authors##"] = implode(', ', $users);
      }

      $datas["##$objettype.openbyuser##"] = '';
      if ($item->getField('users_id_recipient')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_recipient'));
         $datas["##$objettype.openbyuser##"] = $user_tmp->getName();
      }

      $datas["##$objettype.lastupdater##"] = '';
      if ($item->getField('users_id_lastupdater')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
         $datas["##$objettype.lastupdater##"] = $user_tmp->getName();
      }

      $datas["##$objettype.assigntousers##"] = '';
      if ($item->countUsers(CommonITILActor::ASSIGN)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($user_tmp->getFromDB($uid)) {
               $users[$uid] = $user_tmp->getName();
            }
         }
         $datas["##$objettype.assigntousers##"] = implode(', ', $users);
      }

      $datas["##$objettype.assigntosupplier##"] = '';
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmp) {
            $uid           = $tmp['suppliers_id'];
            $supplier_tmp  = new Supplier();
            if ($supplier_tmp->getFromDB($uid)) {
               $suppliers[$uid] = $supplier_tmp->getName();
            }
         }
         $datas["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
      }

      $datas["##$objettype.groups##"] = '';
      if ($item->countGroups(CommonITILActor::REQUESTER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.groups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.observergroups##"] = '';
      if ($item->countGroups(CommonITILActor::OBSERVER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::OBSERVER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.observergroups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.observerusers##"] = '';
      if ($item->countUsers(CommonITILActor::OBSERVER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();
            } else {
               $users[] = $tmp['alternative_email'];
            }
         }
         $datas["##$objettype.observerusers##"] = implode(', ', $users);
      }

      $datas["##$objettype.assigntogroups##"] = '';
      if ($item->countGroups(CommonITILActor::ASSIGN)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $datas["##$objettype.assigntogroups##"] = implode(', ', $groups);
      }

      $datas["##$objettype.solution.type##"]='';
      if ($item->getField('solutiontypes_id')) {
         $datas["##$objettype.solution.type##"]
                              = Dropdown::getDropdownName('glpi_solutiontypes',
                                                          $item->getField('solutiontypes_id'));
      }

      $datas["##$objettype.solution.description##"]
                     = Toolbox::unclean_cross_side_scripting_deep($item->getField('solution'));
      $datas['log'] = [];
      // Use list_limit_max or load the full history ?
      foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $data) {
         $tmp                               = [];
         $tmp["##$objettype.log.date##"]    = $data['date_mod'];
         $tmp["##$objettype.log.user##"]    = $data['user_name'];
         $tmp["##$objettype.log.field##"]   = $data['field'];
         $tmp["##$objettype.log.content##"] = $data['change'];
         $datas['log'][]                    = $tmp;
      }

      $datas["##$objettype.numberoflogs##"] = count($datas['log']);

      // Get unresolved items
      $restrict = "`".$item->getTable()."`.`status`
                     NOT IN ('".implode("', '",
                                        array_merge($item->getSolvedStatusArray(),
                                                    $item->getClosedStatusArray())
                                        )."'
                             )";

      if ($item->maybeDeleted()) {
         $restrict .= " AND `".$item->getTable()."`.`is_deleted` = '0' ";
      }

      $datas["##$objettype.numberofunresolved##"] = countElementsInTableForEntity($item->getTable(),
                                                                                  $this->getEntity(),
                                                                                  $restrict);
      // Document
      $query = "SELECT `glpi_documents`.*
                FROM `glpi_documents`
                LEFT JOIN `glpi_documents_items`
                     ON (`glpi_documents`.`id` = `glpi_documents_items`.`documents_id`)
                WHERE `glpi_documents_items`.`itemtype` =  '$objettype'
                      AND `glpi_documents_items`.`items_id` = '".$item->getField('id')."'";

      $datas["documents"] = [];
      if ($result = $DB->query($query)) {
         while ($data = $DB->fetch_assoc($result)) {
             $tmp                          = [];
             $tmp['##document.id##']       = $data['id'];
             $tmp['##document.name##']     = $data['name'];
             $tmp['##document.weblink##']  = $data['link'];
             $tmp['##document.url##']      = $this->formatURL($options['additionnaloption']['usertype'],
                                                            "document_".$data['id']);
             $tmp['##document.heading##']  = Dropdown::getDropdownName('glpi_documentcategories',
                                                                     $data['documentcategories_id']);
             $tmp['##document.filename##'] = $data['filename'];
             $datas['documents'][] = $tmp;
         }
      }

      $datas["##$objettype.urldocument##"] =
            $this->formatURL($options['additionnaloption']['usertype'],
                             $objettype."_".$item->getField("id").'_Document_Item$1');

      $datas["##$objettype.numberofdocuments##"] = count($datas['documents']);

      return $datas;
   }
}
