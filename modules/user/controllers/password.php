<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2008 Bharat Mediratta
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
class Password_Controller extends Controller {
  public function reset() {
    if (request::method() == "post") {
      $this->_send_reset();
    } else {
      // @todo validate the query key parmeter
      print $this->_reset_form();
    }
  }

  public function do_reset() {
    if (request::method() == "post") {
      $this->_change_password();
    } else {
      print $this->_new_password_form();
    }
  }

  private function _send_reset() {
    $form = $this->_reset_form();
    

    $valid = $form->validate();
    if ($valid) {
      $user = ORM::factory("user")->where("name", $form->reset->inputs["name"]->value)->find();
      if (!$user->loaded || empty($user->email)) {
        $form->reset->inputs["name"]->add_error("no_email", 1);
        $valid = false;
      }
    }

    if ($valid) {
      try {
      $md5 = md5("$user->name; $user->full_name; $user->login_count; $user->last_login");
      $message = new View("reset_password.html");
      $message->url = url::abs_site("password/do_reset?key=$md5");
      $message->name = $user->full_name;
      $message->title = t("Password Reset Request");
      
      Sendmail::factory()
        ->to($user->email)
        ->subject(t("Password Reset Request"))
        ->header("Mime-Version", "1.0")
        ->header("Content-type", "text/html; charset=iso-8859-1")
        ->message($message->render())
        ->send();
      } catch (Exception $e) {
        Kohana::log("error", $e->getMessage() . "\n" . $e->getTraceAsString());
      }

      message::success(t("Password reset email sent"), null);
      print json_encode(
        array("result" => "success"));
    } else {
      print json_encode(
        array("result" => "error",
              "form" => $form->__toString()));
    }
  }

  private function _reset_form() {
    $form = new Forge(url::current(true), "", "post", array("id" => "gResetForm"));
    $group = $form->group("reset")->label(t("Reset Password"));
    $group->input("name")->label(t("Name"))->id("gName")->class(null)->rules("required");
    $group->inputs["name"]->error_messages("no_email", t("No email, unable to reset password"));
    $group->submit("")->value(t("Reset"));

    return $form;
  }

  private function _new_password_form() {
    $form = new Forge("password/do_reset", "", "post", array("id" => "gChangePasswordForm"));
    $group = $form->group("reset")->label(t("Change Password"));
    $group->password("password")->label(t("Password"))->id("gPassword");
    $group->password("password2")->label(t("Confirm Password"))->id("gPassword2");
    $group->inputs["password2"]->error_messages(
      "mistyped", t("The password and the confirm password must match"));
    $group->submit("")->value(t("Update"));

    return $form;
  }
}