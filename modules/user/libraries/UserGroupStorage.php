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
class UserGroupStorage_Core {
  public static $instance;
  protected $driver;

  /**
   * Returns a singleton instance of UserGroupStorage.
   *
   * @return  UserGroupStorage
   */
  static function &instance() {
    if (!isset(UserGroupStorage::$instance)) {
      UserGroupStorage::$instance = new UserGroupStorage();
    }
    return UserGroupStorage::$instance;
  }

  /**
   * Sets up the storage configuration, loads the UserGroupStorage_Driver.
   */
  public function __construct() {
    $driver = "UserGroupStorage_Gallery3_Driver";
    if (!Kohana::auto_load($driver)) {
      throw new Exception("@todo MISSING_STORAGE_DRIVER");
    }
    $this->driver = new $driver;
  }

  /**
   * Return the array of group ids this user belongs to
   * @return array
   */
  public function group_ids() {
    return $this->driver->group_ids();
  }

  /**
   * Return the active user.  If there's no active user, return the guest user.
   * @return User_Model
   */
  public function active_user() {
    return $this->driver->active_user();
  }

  /**
   * Return the guest user.
   * @return User_Model
   */
  public function guest_user() {
    return $this->driver->guest_user();
  }

  /**
   * Change the active user.
   * @return User_Model
   */
  public function set_active_user($user) {
    return $this->driver->set_active_user($user);
  }

  /**
   * Create a new user.
   *
   * @param string  $name
   * @param string  $full_name
   * @param string  $password
   * @return User_Model
   */
  public function create_user($name, $full_name, $password) {
    return $this->driver->create_user($name, $full_name, $password);
  }

  /**
   * Is the password provided correct?
   *
   * @param user User_Model
   * @param string $password a plaintext password
   * @return boolean true if the password is correct
   */
  public function is_correct_password($user, $password) {
    return $this->driver->is_correct_password($user, $password);
  }

  /**
   * Create the hashed passwords.
   * @param string $password a plaintext password
   * @return string hashed password
   */
  public function hash_password($password) {
    return $this->driver->hash_password($password);
  }

  /**
   * Log in as a given user.
   * @param User_Model $user the user object.
   */
  public function login($user) {
    return $this->driver->login($user);
  }

  /**
   * Log out the active user and destroy the session.
   * @param User_Model $user the user object.
   */
  public function logout() {
    return $this->driver->logout();
  }

  /**
   * Look up a user by id.
   * @param integer      $id the user id
   * @return User_Model  the user object, or null if the id was invalid.
   */
  public function lookup_user($id) {
    return $this->driver->lookup_user($id);
  }

  /**
   * Look up a user by name.
   * @param integer      $id the user name
   * @return User_Model  the user object, or null if the name was invalid.
   */
  public function lookup_user_by_name($name) {
    return $this->driver->lookup_user_by_name($name);
  }

  /**
   * Create a new group.
   *
   * @param string  $name
   * @return Group_Model
   */
  public function create_group($name) {
    return $this->driver->create_group($name);
  }

  /**
   * The group of all possible visitors.  This includes the guest user.
   *
   * @return Group_Model
   */
  public function everybody_group() {
    return $this->driver->everybody_group();
  }

  /**
   * The group of all logged-in visitors.  This does not include guest users.
   *
   * @return Group_Model
   */
  public function registered_users_group() {
    return $this->driver->registered_users_group();
  }

  /**
   * Look up a group by id.
   * @param integer       $id the group id
   * @return Group_Model  the group object, or null if the id was invalid.
   */
  public function lookup_group($id) {
    return $this->driver->lookup_group($id);
  }

  /**
   * Look up a group by name.
   * @param integer       $id the group name
   * @return Group_Model  the group object, or null if the name was invalid.
   */
  public function lookup_group_by_name($name) {
    return $this->driver->lookup_group_by_name($name);
  }
}