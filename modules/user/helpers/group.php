<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2009 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * This is the API for handling groups.
 *
 * Note: by design, this class does not do any permission checking.
 */
class group_Core {
  /**
   * Create a new group.
   *
   * @param string  $name
   * @return Group_Model
   */
  static function create($name) {
    return UserGroupStorage::instance()->create_group($name);
  }

  /**
   * The group of all possible visitors.  This includes the guest user.
   *
   * @return Group_Model
   */
  static function everybody() {
    return UserGroupStorage::instance()->everybody_group();
  }

  /**
   * The group of all logged-in visitors.  This does not include guest users.
   *
   * @return Group_Model
   */
  static function registered_users() {
    return UserGroupStorage::instance()->registered_users_group();
  }

  /**
   * Look up a group by id.
   * @param integer       $id the group id
   * @return Group_Model  the group object, or null if the name was invalid.
   */
  static function lookup($id) {
    return UserGroupStorage::instance()->lookup_group($id);
  }

  /**
   * Look up a group by name.
   * @param integer       $name the group name
   * @return Group_Model  the group object, or null if the name was invalid.
   */
  static function lookup_by_name($name) {
    return UserGroupStorage::instance()->lookup_group_by_name($name);
  }

  // ----------------------------------------------------------------------
  // Code below applies to all drivers
  // ----------------------------------------------------------------------

  static function get_edit_form_admin($group) {
    $form = new Forge(
      "admin/users/edit_group/$group->id", "", "post", array("id" => "gEditGroupForm"));
    $form_group = $form->group("edit_group")->label(t("Edit Group"));
    $form_group->input("name")->label(t("Name"))->id("gName")->value($group->name);
    $form_group->inputs["name"]->error_messages(
      "in_use", t("There is already a group with that name"));
    $form_group->submit("")->value(t("Save"));
    $form->add_rules_from($group);
    return $form;
  }

  static function get_add_form_admin() {
    $form = new Forge("admin/users/add_group", "", "post", array("id" => "gAddGroupForm"));
    $form_group = $form->group("add_group")->label(t("Add Group"));
    $form_group->input("name")->label(t("Name"))->id("gName");
    $form_group->inputs["name"]->error_messages(
      "in_use", t("There is already a group with that name"));
    $form_group->submit("")->value(t("Add Group"));
    $group = ORM::factory("group");
    $form->add_rules_from($group);
    return $form;
  }

  static function get_delete_form_admin($group) {
    $form = new Forge("admin/users/delete_group/$group->id", "", "post",
                      array("id" => "gDeleteGroupForm"));
    $form_group = $form->group("delete_group")->label(
      t("Are you sure you want to delete group %group_name?", array("group_name" => $group->name)));
    $form_group->submit("")->value(t("Delete"));
    return $form;
  }
}
