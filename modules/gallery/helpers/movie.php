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
 * This is the API for handling movies.
 *
 * Note: by design, this class does not do any permission checking.
 */
class movie_Core {
  /**
   * Create a new movie.
   * @param integer $parent_id id of parent album
   * @param string  $filename path to the photo file on disk
   * @param string  $name the filename to use for this photo in the album
   * @param integer $title the title of the new photo
   * @param string  $description (optional) the longer description of this photo
   * @param string  $slug (optional) the url component for this photo
   * @return Item_Model
   */
  static function create($parent, $filename, $name, $title,
                         $description=null, $owner_id=null, $slug=null) {
    if (!$parent->loaded || !$parent->is_album()) {
      throw new Exception("@todo INVALID_PARENT");
    }

    if (!is_file($filename)) {
      throw new Exception("@todo MISSING_MOVIE_FILE");
    }

    if (strpos($name, "/")) {
      throw new Exception("@todo NAME_CANNOT_CONTAIN_SLASH");
    }

    // We don't allow trailing periods as a security measure
    // ref: http://dev.kohanaphp.com/issues/684
    if (rtrim($name, ".") != $name) {
      throw new Exception("@todo NAME_CANNOT_END_IN_PERIOD");
    }

    try {
      $movie_info = movie::getmoviesize($filename);
    } catch (Exception $e) {
      // Assuming this is MISSING_FFMPEG for now
      $movie_info = getimagesize(MODPATH . "gallery/images/missing_movie.png");
    }

    // Force an extension onto the name
    $pi = pathinfo($filename);
    if (empty($pi["extension"])) {
      $pi["extension"] = image_type_to_extension($movie_info[2], false);
      $name .= "." . $pi["extension"];
    }

    if (empty($slug)) {
      $slug = item::convert_filename_to_slug($name);
    }

    $movie = ORM::factory("item");
    $movie->type = "movie";
    $movie->title = $title;
    $movie->description = $description;
    $movie->name = $name;
    $movie->owner_id = $owner_id ? $owner_id : identity::active_user()->id;
    $movie->width = $movie_info[0];
    $movie->height = $movie_info[1];
    $movie->mime_type = strtolower($pi["extension"]) == "mp4" ? "video/mp4" : "video/x-flv";
    $movie->thumb_dirty = 1;
    $movie->resize_dirty = 1;
    $movie->sort_column = "weight";
    $movie->slug = $slug;
    $movie->rand_key = ((float)mt_rand()) / (float)mt_getrandmax();

    // Randomize the name if there's a conflict
    // @todo Improve this.  Random numbers are not user friendly
    while (ORM::factory("item")
           ->where("parent_id", $parent->id)
           ->open_paren()
           ->where("name", $movie->name)
           ->orwhere("slug", $movie->slug)
           ->close_paren()
           ->find()->id) {
      $rand = rand();
      $movie->name = "{$name}.$rand.{$pi['extension']}";
      $movie->slug = "{$slug}-$rand";
    }

    // This saves the photo
    $movie->add_to_parent($parent);

    // If the thumb or resize already exists then rename it
    if (file_exists($movie->resize_path()) ||
        file_exists($movie->thumb_path())) {
      $movie->name = $pi["filename"] . "-" . rand() . "." . $pi["extension"];
      $movie->save();
    }

    copy($filename, $movie->file_path());

    // @todo: publish this from inside Item_Model::save() when we refactor to the point where
    // there's only one save() happening here.
    module::event("item_created", $movie);

    // Build our thumbnail
    graphics::generate($movie);

    // If the parent has no cover item, make this it.
    if (access::can("edit", $parent) && $parent->album_cover_item_id == null)  {
      item::make_album_cover($movie);
    }

    return $movie;
  }

  static function get_edit_form($movie) {
    $form = new Forge("movies/$movie->id", "", "post", array("id" => "g-edit-movie-form"));
    $form->hidden("_method")->value("put");
    $group = $form->group("edit_item")->label(t("Edit Movie"));
    $group->input("title")->label(t("Title"))->value($movie->title);
    $group->textarea("description")->label(t("Description"))->value($movie->description);
    $group->input("filename")->label(t("Filename"))->value($movie->name)
      ->rules("required")
      ->error_messages(
        "name_conflict", t("There is already a movie, photo or album with this name"))
      ->callback("item::validate_no_slashes")
      ->error_messages("no_slashes", t("The movie name can't contain a \"/\""))
      ->callback("item::validate_no_trailing_period")
      ->error_messages("no_trailing_period", t("The movie name can't end in \".\""))
      ->error_messages("illegal_extension", t("You cannot change the filename extension"));
    $group->input("slug")->label(t("Internet Address"))->value($movie->slug)
      ->callback("item::validate_url_safe")
      ->error_messages(
        "slug_conflict", t("There is already a movie, photo or album with this internet address"))
      ->error_messages(
        "not_url_safe",
        t("The internet address should contain only letters, numbers, hyphens and underscores"));

    module::event("item_edit_form", $movie, $form);

    $group = $form->group("buttons")->label("");
    $group->submit("")->value(t("Modify"));
    $form->add_rules_from(ORM::factory("item"));
    return $form;
  }


  static function getmoviesize($filename) {
    $ffmpeg = self::find_ffmpeg();
    if (empty($ffmpeg)) {
      throw new Exception("@todo MISSING_FFMPEG");
    }

    $cmd = escapeshellcmd($ffmpeg) . " -i " . escapeshellarg($filename) . " 2>&1";
    $result = `$cmd`;
    if (preg_match("/Stream.*?Video:.*?(\d+)x(\d+)/", $result, $regs)) {
      list ($width, $height) = array($regs[1], $regs[2]);
    } else {
      list ($width, $height) = array(0, 0);
    }
    return array($width, $height);
  }

  static function extract_frame($input_file, $output_file) {
    $ffmpeg = self::find_ffmpeg();
    if (empty($ffmpeg)) {
      throw new Exception("@todo MISSING_FFMPEG");
    }

    $cmd = escapeshellcmd($ffmpeg) . " -i " . escapeshellarg($input_file) .
      " -an -ss 00:00:03 -an -r 1 -vframes 1" .
      " -y -f mjpeg " . escapeshellarg($output_file) . " 2>&1";
    exec($cmd);
  }

  static function find_ffmpeg() {
    if (!$ffmpeg_path = module::get_var("gallery", "ffmpeg_path")) {
      $graphics_path = module::get_var("gallery", "graphics_toolkit_path", null);

      putenv("PATH=" . getenv("PATH") . (empty($graphics_path) ? "" : ":$graphics_path") .
             ":/usr/local/bin:/opt/local/bin:/opt/bin");
      if (function_exists("exec")) {
        $ffmpeg_path = exec("which ffmpeg");
      }

      module::set_var("gallery", "ffmpeg_path", $ffmpeg_path);
    }
    return $ffmpeg_path;
  }
}
