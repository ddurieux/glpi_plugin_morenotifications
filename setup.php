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

define ("PLUGIN_MORENOTIFICATIONS_VERSION","0.84+1.0");

// Init the hooks of morenotifications
function plugin_init_morenotifications() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['morenotifications'] = true;

   $Plugin = new Plugin();
   if ($Plugin->isActivated('morenotifications')) {

         Plugin::registerClass('PluginMorenotificationsGeneral',
         		array('notificationtemplates_types' => true));
         Plugin::registerClass('PluginMorenotificationsTicketnotsolved');
         Plugin::registerClass('PluginMorenotificationsEntity',
         		array('addtabon' => 'Entity'));

         $PLUGIN_HOOKS['item_get_datas']['morenotifications'] =
                 array('NotificationTargetTicket' => 'plugin_morenotifications_notiftag');
   }
   return $PLUGIN_HOOKS;
}

// Name and Version of the plugin
function plugin_version_morenotifications() {
   return array('name'           => 'More notifications',
                'shortname'      => 'morenotifications',
                'version'        => PLUGIN_MORENOTIFICATIONS_VERSION,
                'license'        => 'AGPLv3+',
                'author'         =>'<a href="mailto:d.durieux@siprossii.com">David DURIEUX</a>',
                'homepage'       =>'https://',
                'minGlpiVersion' => '0.84'
   );
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_morenotifications_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'0.84','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
      echo "error, require GLPI 0.84.x";
   } else {
      return true;
   }
}

function plugin_morenotifications_check_config() {
   return true;
}


?>