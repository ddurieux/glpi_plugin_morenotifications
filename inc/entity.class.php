<?php

/*
   ------------------------------------------------------------------------
   Plugin Morenotifications for GLPI
   Copyright (C) 2014-2015 by the Plugin Morenotifications for David Durieux.

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
   @copyright Copyright (c) 2011-2015 Plugin Morenotifications for David Durieux
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://github.com/ddurieux/glpi_plugin_morenotifications
   @since     2014

   ------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMorenotificationsEntity extends CommonDBTM {

   /**
   * Get name of this type
   *
   * @return text name of this type by language of the user connected
   *
   **/
   static function getTypeName($nb=0) {
      return _n('Entity', 'Entities', $nb);
   }



   static function canCreate() {
      return Session::haveRight('entity', 'w');
   }


   static function canView() {
      return Session::haveRight('entity', 'r');
   }



   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      $itemtype = $item->getType();
      if ($itemtype == 'Entity') {
         return __('More notifications', 'morenotifications');
      }
      return '';
   }



   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $pmEntity = new self();
      if ($item->getID() >= 0) {
         $pmEntity->showForm($item->getID());
      }

      return true;
   }



   /**
   * Display form for configuration
   *
   * @param $items_id integer ID
   * @param $options array
   *
   *@return bool true if form is ok
   *
   **/
   function showForm($entities_id, $options=array()) {
      global $DB,$CFG_GLPI;

      $a_entities = $this->find("`entities_id`='".$entities_id."'", "", 1);
      if (count($a_entities) == '1') {
         $a_entity= current($a_entities);
         $this->getFromDB($a_entity['id']);
      } else {
         $this->getEmpty();
         $this->fields['ticketnotclosed'] = '-1';
         $this->fields['ticketwaiting'] = '-1';
      }

      $this->showFormHeader($options);

      $elements = array(
          '-1' => __('Inheritance of the parent entity'),
          '0'  => __('Never'),
          '1'  => __('Next day + every day', 'morenotifications'),
          '2'  => __('Next day + every 2 days', 'morenotifications'),
          '3'  => __('Next day + every 3 days', 'morenotifications'),
          '4'  => __('Next day + every 4 days', 'morenotifications'),
          '5'  => __('Next day + every 5 days', 'morenotifications'),
          '6'  => __('Next day + every 6 days', 'morenotifications'),
          '7'  => __('Next day + every 7 days', 'morenotifications'),
          '8'  => __('Next day + every 8 days', 'morenotifications'),
          '9'  => __('Next day + every 9 days', 'morenotifications'),
          '10' => __('Next day + every 10 days', 'morenotifications'),
          '11' => __('Next day + every 11 days', 'morenotifications'),
          '12' => __('Next day + every 12 days', 'morenotifications'),
          '13' => __('Next day + every 13 days', 'morenotifications'),
          '14' => __('Next day + every 14 days', 'morenotifications'),
          '15' => __('Next day + every 15 days', 'morenotifications'),
          '16' => __('Next day + every 16 days', 'morenotifications'),
          '17' => __('Next day + every 17 days', 'morenotifications'),
          '18' => __('Next day + every 18 days', 'morenotifications'),
          '19' => __('Next day + every 19 days', 'morenotifications'),
          '20' => __('Next day + every 20 days', 'morenotifications'),
          '21' => __('Next day + every 21 days', 'morenotifications'),
          '22' => __('Next day + every 22 days', 'morenotifications'),
          '23' => __('Next day + every 23 days', 'morenotifications'),
          '24' => __('Next day + every 24 days', 'morenotifications'),
          '25' => __('Next day + every 25 days', 'morenotifications'),
      );

      echo "<tr>";
      echo "<td>";
      echo "<input type='hidden' name='entities_id' value='".$entities_id."' />";
      echo __('Tickets not solved / not closed (with due date)', 'morenotifications')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      if ($entities_id == '0') {
         unset($elements['-1']);
      }
      Dropdown::showFromArray("ticketnotclosed", $elements, array('value' => $this->fields['ticketnotclosed']));
      echo "</td>";

      echo "<td>";
      echo __('Tickets waiting', 'morenotifications')."&nbsp;:";
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray("ticketwaiting", $elements, array('value' => $this->fields['ticketwaiting']));
      echo "</td>";
      echo "</tr>";

      // Inheritance
      if ($entities_id > 0) {

         echo "<tr class='tab_bg_1'>";
         if ($this->fields['ticketnotclosed'] == '-1') {
            echo "<td colspan='2' class='green center'>";
            echo __('Inheritance of the parent entity')."&nbsp;:&nbsp;";
            $val = $this->getValueAncestor("ticketnotclosed", $entities_id);
            echo $elements[$val];
            echo "</td>";
         } else {
            echo "<td colspan='2'>";
            echo "</td>";
         }
         if ($this->fields['ticketwaiting'] == '-1') {
            echo "<td colspan='2' class='green center'>";
            echo __('Inheritance of the parent entity')."&nbsp;:&nbsp;";
            $val = $this->getValueAncestor("ticketwaiting", $entities_id);
            echo $elements[$val];
            echo "</td>";
         } else {
            echo "<td colspan='2'>";
            echo "</td>";
         }
         echo "</tr>";
      }


      $this->showFormButtons($options);

      return true;
   }



/**
    * Get value of config
    *
    * @global object $DB
    * @param value $name field name
    * @param integer $entities_id
    *
    * @return value of field
    */
   function getValueAncestor($name, $entities_id) {
      global $DB;

      $entities_ancestors = getAncestorsOf("glpi_entities", $entities_id);

      $nbentities = count($entities_ancestors);
      for ($i=0; $i<$nbentities; $i++) {
         $entity = array_pop($entities_ancestors);
         $query = "SELECT * FROM `".$this->getTable()."`
            WHERE `entities_id`='".$entity."'
               AND `".$name."` IS NOT NULL
            LIMIT 1";
         $result = $DB->query($query);
         if ($DB->numrows($result) != '0') {
            $data = $DB->fetch_assoc($result);
            return $data[$name];
         }
      }
      $this->getFromDB(1);
      return $this->getField($name);
   }



   /**
    * Get the value (of this entity or parent entity or in general config
    *
    * @global object $DB
    * @param value $name field name
    * @param integet $entities_id
    *
    * @return value value of this field
    */
   function getValue($name, $entities_id) {
      global $DB;

      $query = "SELECT `".$name."` FROM `".$this->getTable()."`
         WHERE `entities_id`='".$entities_id."'
            AND `".$name."` IS NOT NULL
         LIMIT 1";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_assoc($result);
         return $data[$name];
      }
      return $this->getValueAncestor($name, $entities_id);
   }

}

?>