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
abstract class UserGroupStorage_Driver {
  /**
   * Return the array of group ids this user belongs to
   * @return array
   */
  abstract function group_ids();

  /**
   * Return the active user.  If there's no active user, return the guest user.
   * @return User_Model
   */
  abstract function active_user();

  /**
   * Return the guest user.
   * @return User_Model
   */
  abstract function guest_user();

  /**
   * Change the active user.
   * @return User_Model
   */
  abstract function set_active_user($user);

  /**
   * Create a new user.
   *
   * @param string  $name
   * @param string  $full_name
   * @param string  $password
   * @return User_Model
   */
  abstract function create_user($name, $full_name, $password);

  /**
   * Is the password provided correct?
   *
   * @param user User_Model
   * @param string $password a plaintext password
   * @return boolean true if the password is correct
   */
  abstract function is_correct_password($user, $password);

  /**
   * Log in as a given user.
   * @param User_Model $user the user object.
   */
  abstract function login($user);

  /**
   * Log out the active user and destroy the session.
   * @param User_Model $user the user object.
   */
  abstract function logout();

  /**
   * Look up a user by id.
   * @param integer      $id the user id
   * @return User_Model  the user object, or null if the id was invalid.
   */
  abstract function lookup_user($id);

  /**
   * Look up a user by name.
   * @param integer      $id the user name
   * @return User_Model  the user object, or null if the name was invalid.
   */
  abstract function lookup_user_by_name($name);

  /**
   * Create a new group.
   *
   * @param string  $name
   * @return Group_Model
   */
  abstract function create_group($name);

  /**
   * The group of all possible visitors.  This includes the guest user.
   *
   * @return Group_Model
   */
  abstract function everybody_group();

  /**
   * The group of all logged-in visitors.  This does not include guest users.
   *
   * @return Group_Model
   */
  abstract function registered_users_group();

  /**
   * Look up a group by id.
   * @param integer       $id the group id
   * @return Group_Model  the group object, or null if the id was invalid.
   */
  abstract function lookup_group($id);

  /**
   * Look up a group by name.
   * @param integer       $id the group name
   * @return Group_Model  the group object, or null if the name was invalid.
   */
  abstract function lookup_group_by_name($name);
}