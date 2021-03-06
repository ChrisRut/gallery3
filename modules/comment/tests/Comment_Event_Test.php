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
class Comment_Event_Test extends Unit_Test_Case {
  public function deleting_an_item_deletes_its_comments_too_test() {
    $rand = rand();
    $album = album::create(ORM::factory("item", 1), "test_$rand", "test_$rand");
    $comment = comment::create(
      $album, identity::guest(), "text_$rand", "name_$rand", "email_$rand", "url_$rand");

    $album->delete();

    $deleted_comment = ORM::factory("comment", $comment->id);
    $this->assert_false($deleted_comment->loaded);
  }
}
