# Plugin morenotifications for GLPI

This plugin has features:

* Send notification for tickets late day after + all xxx days (configuration in entity form)
* Send notification for tickets in waiting state day after + all xxx days (configuration in entity form)
* Add new notification tags for tickets:
  * ##entity.notes##
  * ##ticket.item.tech.name## (use it with FOREACH)
  * ##ticket.item.tech.phone## (use it with FOREACH)
  * ##ticket.item.tech.mobile## (use it with FOREACH)
  * ##ticket.item.tech.email## (use it with FOREACH)
  * ##ticket.calendardetails## (calendar of entity of ticket with working hours)

