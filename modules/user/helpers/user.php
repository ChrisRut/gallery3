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
 * This is the API for handling users.
 *
 * Note: by design, this class does not do any permission checking.
 */
class user_Core {
  /**
   * Return the array of group ids this user belongs to.
   *
   * @return array
   */
  static function group_ids() {
    return UserGroupStorage::instance()->group_ids();
  }

  /**
   * Return the active user.  If there's no active user, return the guest user.
   *
   * @return User_Model
   */
  static function active() {
    return UserGroupStorage::instance()->active_user();
  }

  /**
   * Return the guest user.
   *
   * @return User_Model
   */
  static function guest() {
    return UserGroupStorage::instance()->guest_user();
  }

  /**
   * Change the active user.
   *
   * @return User_Model
   */
  static function set_active($user) {
    return UserGroupStorage::instance()->set_active_user($user);
  }

  /**
   * Create a new user.
   *
   * @param string  $name
   * @param string  $full_name
   * @param string  $password
   * @return User_Model
   */
  static function create($name, $full_name, $password) {
    return UserGroupStorage::instance()->create_user($name, $full_name, $password);
  }

  /**
   * Is the password provided correct?
   *
   * @param user User Model
   * @param string $password a plaintext password
   * @return boolean true if the password is correct
   */
  static function is_correct_password($user, $password) {
    return UserGroupStorage::instance()->is_correct_password($user, $password);
  }

  /**
   * Create the hashed passwords.
   * @param string $password a plaintext password
   * @return string hashed password
   */
  static function hash_password($password) {
    return UserGroupStorage::instance()->hash_password($password);
  }

  /**
   * Log in as a given user.
   * @param object $user the user object.
   */
  static function login($user) {
    UserGroupStorage::instance()->login($user);
  }

  /**
   * Log out the active user and destroy the session.
   * @param object $user the user object.
   */
  static function logout() {
    UserGroupStorage::instance()->logout();
  }

  /**
   * Look up a user by id.
   * @param integer      $id the user id
   * @return User_Model  the user object, or null if the id was invalid.
   */
  static function lookup($id) {
    return UserGroupStorage::instance()->lookup_user($id);
  }

  /**
   * Look up a user by name.
   * @param integer      $id the user name
   * @return User_Model  the user object, or null if the name was invalid.
   */
  static function lookup_by_name($name) {
    return UserGroupStorage::instance()->lookup_user_by_name($name);
  }

  // ----------------------------------------------------------------------
  // Code below applies to all drivers
  // ----------------------------------------------------------------------

  static function get_edit_form($user) {
    $form = new Forge("users/$user->id?_method=put", "", "post", array("id" => "gEditUserForm"));
    $group = $form->group("edit_user")->label(t("Edit User: %name", array("name" => $user->name)));
    $group->input("full_name")->label(t("Full Name"))->id("gFullName")->value($user->full_name);
    self::_add_locale_dropdown($group, $user);
    $group->password("password")->label(t("Password"))->id("gPassword");
    $group->password("password2")->label(t("Confirm Password"))->id("gPassword2")
      ->matches($group->password);
    $group->input("email")->label(t("Email"))->id("gEmail")->value($user->email);
    $group->input("url")->label(t("URL"))->id("gUrl")->value($user->url);
    $form->add_rules_from($user);

    module::event("user_edit_form", $user, $form);
    $group->submit("")->value(t("Save"));
    return $form;
  }

  static function get_edit_form_admin($user) {
    $form = new Forge(
      "admin/users/edit_user/$user->id", "", "post", array("id" => "gEditUserForm"));
    $group = $form->group("edit_user")->label(t("Edit User"));
    $group->input("name")->label(t("Username"))->id("gUsername")->value($user->name);
    $group->inputs["name"]->error_messages(
      "in_use", t("There is already a user with that username"));
    $group->input("full_name")->label(t("Full Name"))->id("gFullName")->value($user->full_name);
    self::_add_locale_dropdown($group, $user);
    $group->password("password")->label(t("Password"))->id("gPassword");
    $group->password("password2")->label(t("Confirm Password"))->id("gPassword2")
      ->matches($group->password);
    $group->input("email")->label(t("Email"))->id("gEmail")->value($user->email);
    $group->input("url")->label(t("URL"))->id("gUrl")->value($user->url);
    $group->checkbox("admin")->label(t("Admin"))->id("gAdmin")->checked($user->admin);
    $form->add_rules_from($user);
    $form->edit_user->password->rules("-required");

    module::event("user_edit_form_admin", $user, $form);
    $group->submit("")->value(t("Modify User"));
    return $form;
  }

  static function get_add_form_admin() {
    $form = new Forge("admin/users/add_user", "", "post", array("id" => "gAddUserForm"));
    $group = $form->group("add_user")->label(t("Add User"));
    $group->input("name")->label(t("Username"))->id("gUsername")
      ->error_messages("in_use", t("There is already a user with that username"));
    $group->input("full_name")->label(t("Full Name"))->id("gFullName");
    $group->password("password")->label(t("Password"))->id("gPassword");
    $group->password("password2")->label(t("Confirm Password"))->id("gPassword2")
      ->matches($group->password);
    $group->input("email")->label(t("Email"))->id("gEmail");
    $group->input("url")->label(t("URL"))->id("gUrl");
    self::_add_locale_dropdown($group);
    $group->checkbox("admin")->label(t("Admin"))->id("gAdmin");
    $user = ORM::factory("user");
    $form->add_rules_from($user);

    module::event("user_add_form_admin", $user, $form);
    $group->submit("")->value(t("Add User"));
    return $form;
  }

  private static function _add_locale_dropdown(&$form, $user=null) {
    $locales = locales::installed();
    foreach ($locales as $locale => $display_name) {
      $locales[$locale] = SafeString::of_safe_html($display_name);
    }
    if (count($locales) > 1) {
      // Put "none" at the first position in the array
      $locales = array_merge(array("" => t("« none »")), $locales);
      $selected_locale = ($user && $user->locale) ? $user->locale : "";
      $form->dropdown("locale")
        ->label(t("Language Preference"))
        ->options($locales)
        ->selected($selected_locale);
    }
  }

  static function get_delete_form_admin($user) {
    $form = new Forge("admin/users/delete_user/$user->id", "", "post",
                      array("id" => "gDeleteUserForm"));
    $group = $form->group("delete_user")->label(
      t("Are you sure you want to delete user %name?", array("name" => $user->name)));
    $group->submit("")->value(t("Delete user %name", array("name" => $user->name)));
    return $form;
  }

  static function get_login_form($url) {
    $form = new Forge($url, "", "post", array("id" => "gLoginForm"));
    $group = $form->group("login")->label(t("Login"));
    $group->input("name")->label(t("Username"))->id("gUsername")->class(null);
    $group->password("password")->label(t("Password"))->id("gPassword")->class(null);
    $group->inputs["name"]->error_messages("invalid_login", t("Invalid name or password"));
    $group->submit("")->value(t("Login"));
    return $form;
  }

  static function cookie_locale() {
    $cookie_data = Input::instance()->cookie("g_locale");
    $locale = null;
    if ($cookie_data) {
      if (preg_match("/^([a-z]{2,3}(?:_[A-Z]{2})?)$/", trim($cookie_data), $matches)) {
        $requested_locale = $matches[1];
        $installed_locales = locales::installed();
        if (isset($installed_locales[$requested_locale])) {
          $locale = $requested_locale;
        }
      }
    }
    return $locale;
  }
}