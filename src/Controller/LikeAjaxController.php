<?php

namespace Drupal\likeajax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AlertCommand;

const RETURN_AFFECTED = 2;


class LikeAjaxController extends ControllerBase {

  public function content() {
    $response = new AjaxResponse();

    if(isset($_GET)) {
      $code = \Drupal::request()->query->get('code');
      /**
       * Confirm mail
       */
      if($code != null) {
        $connection = \Drupal::database();
        $query = $connection->query("SELECT verified FROM likeajax_likes WHERE code = :code;", [
          ':code' => $code,
        ]);
        $result = $query->fetch();
        if(isset($result->verified) && intval($result->verified) == 1) {
          $response->addCommand(new AlertCommand("Głos został zweryfikowany wcześniej"));
        } else {
          $num_updated = $connection->update('likeajax_likes')
            ->fields([
              'verified' => 1,
            ])
            ->condition('code', $code)
            ->execute();
          // $query = $connection->query("UPDATE likeajax_likes SET verified = 1 WHERE code = :code;", [
          //   ':code' => $code,
          // ]);
          if($num_updated == 0) {
            $response->addCommand(new AlertCommand("Błędny kod"));
          } else if ($num_updated > 0) {
            $response->addCommand(new AlertCommand("Głos został zweryfikowany. Dziękujemy!"));
          } else {
            $response->addCommand(new AlertCommand("Wystąpił błąd! Proszę skontaktować się z administratorem!"));
          }
        }
      }
    }

    $connection = \Drupal::database();
    $query = $connection->query("SELECT dyplom, COUNT(id) AS votes FROM likeajax_likes WHERE verified = 1 GROUP BY dyplom;");
    $result = $query->fetchAll();

    $response->addCommand(new AlertCommand($result));
    return $response;
  }

}