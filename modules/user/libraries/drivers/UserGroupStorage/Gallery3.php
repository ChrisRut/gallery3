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

class UserGroupStorage_Gallery3_Driver extends UserGroupStorage_Driver {
  public function group_ids() {
    $session = Session::instance();
    if (!($ids = $session->get("group_ids"))) {
      $ids = array();
      foreach ($user->groups as $group) {
        $ids[] = $group->id;
      }
      $session->set("group_ids", $ids);
    }
    return $ids;
  }

  public function active_user() {
    // @todo (maybe) cache this object so we're not always doing session lookups.
    $session = Session::instance();
    $user = $session->get("user", null);
    if (!isset($user)) {
      // Don't do this as a fallback in the Session::get() call because it can trigger unnecessary
      // work.
      $session->set("user", $user = user::guest());

      // The installer cannot set a user into the session, so it just sets an id which we should
      // upconvert into a user.
      if ($user === 2) {
        $user = model_cache::get("user", 2);
        user::login($user);
        $session->set("user", $user);
      }

    }
    return $user;
  }

  public function guest_user() {
    return model_cache::get("user", 1);
  }

  public function set_active_user($user) {
    $session = Session::instance();
    $session->set("user", $user);
    $session->delete("group_ids");
  }

  public function create_user($name, $full_name, $password) {
    $user = ORM::factory("user")->where("name", $name)->find();
    if ($user->loaded) {
      throw new Exception("@todo USER_ALREADY_EXISTS $name");
    }

    $user->name = $name;
    $user->full_name = $full_name;
    $user->password = $password;

    // Required groups
    $user->add(group::everybody());
    $user->add(group::registered_users());

    $user->save();
    return $user;
  }

  public function is_correct_password($user, $password) {
    $valid = $user->password;

    // Try phpass first, since that's what we generate.
    if (strlen($valid) == 34) {
      require_once(MODPATH . "user/lib/PasswordHash.php");
      $hashGenerator = new PasswordHash(10, true);
      return $hashGenerator->CheckPassword($password, $valid);
    }

    $salt = substr($valid, 0, 4);
    // Support both old (G1 thru 1.4.0; G2 thru alpha-4) and new password schemes:
    $guess = (strlen($valid) == 32) ? md5($password) : ($salt . md5($salt . $password));
    if (!strcmp($guess, $valid)) {
      return true;
    }

    // Passwords with <&"> created by G2 prior to 2.1 were hashed with entities
    $sanitizedPassword = html::specialchars($password, false);
    $guess = (strlen($valid) == 32) ? md5($sanitizedPassword)
          : ($salt . md5($salt . $sanitizedPassword));
    if (!strcmp($guess, $valid)) {
      return true;
    }

    return false;
  }

  public function hash_password($password) {
    require_once(MODPATH . "user/lib/PasswordHash.php");
    $hashGenerator = new PasswordHash(10, true);
    return $hashGenerator->HashPassword($password);
  }

  public function login($user) {
    $user->login_count += 1;
    $user->last_login = time();
    $user->save();

    user::set_active($user);
  }

  public function logout() {
    try {
      Session::instance()->destroy();
    } catch (Exception $e) {
      Kohana::log("error", $e);
    }
  }

  public function lookup_user($id) {
    $user = model_cache::get("user", $id);
    if ($user->loaded) {
      return $user;
    }
    return null;
  }

  public function lookup_user_by_name($name) {
    $user = model_cache::get("user", $name, "name");
    if ($user->loaded) {
      return $user;
    }
    return null;
  }

  public function lookup_group($id) {
    $user = model_cache::get("group", $id);
    if ($user->loaded) {
      return $user;
    }
    return null;
  }

  public function lookup_group_by_name($name) {
    $group = model_cache::get("group", $name, "name");
    if ($group->loaded) {
      return $group;
    }
    return null;
  }

  public function create_group($name) {
    $group = ORM::factory("group")->where("name", $name)->find();
    if ($group->loaded) {
      throw new Exception("@todo GROUP_ALREADY_EXISTS $name");
    }

    $group->name = $name;
    $group->save();

    return $group;
  }

  public function everybody_group() {
    return model_cache::get("group", 1);
  }

  public function registered_users_group() {
    return model_cache::get("group", 2);
  }
}