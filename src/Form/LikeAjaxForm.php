<?php

namespace Drupal\likeajax\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Form for LikeAjaxBlock
 */
class LikeAjaxForm extends FormBase {

    /**
     * (@inheritdoc)
     */
    public function getFormId() {
        return 'likeajax_form';
    }

    /**
     * (@inheritdoc)
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['message'] = [
            '#type' => 'markup',
            '#markup' => '<div class="result_message"></div>',
        ];
        $form['email_input'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#attributes' => array(
                'placeholder' => t('Twój email'),
              ),
        ];
        $form['id_input'] = [
            '#type' => 'number',
            '#title' => $this->t('ID'),
        ];
        $form['tos_cb'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Zapoznałem/am się z polityką prywatności FONT nie czcionka! i wyrażam zgodę na przesłanie e-maila weryfikacyjnego w celu oddania głosu'),
            '#required' => FALSE,
            '#default_value' => FALSE,
        ];
        $form['error_tos'] = [
            '#type' => 'markup',
            '#markup' => '<span class="error_tos"></span>',
        ];
        $form['mailing_cb'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Wyrażam zgodę na przesyłanie mailingu FONT nie czcionka!'),
            '#required' => FALSE,
            '#default_value' => FALSE,
        ];
        // $form['form_submit'] = [
        //     '#type' => 'submit',
        //     '#value' => $this->t('Oddaj głos'),
        // ];
        $form['actions'] = [
            '#type' => 'button',
            '#value' => $this->t('Oddaj głos'),
            '#ajax' => [
                'callback' => '::setMessage',
            ],
        ];

        $form['#attached']['library'][] = 'likeajax/form_functions';

        return $form;
    }

    public function setMessage(array &$form, FormStateInterface $form_state) {
        $dyplom = $form_state->getValue('id_input');
        $email = $form_state->getValue('email_input');
        $tos = $form_state->getValue('tos_cb');
        $mailing = $form_state->getValue('mailing_cb');
        $response = new AjaxResponse();
        /**
         * ToS not accepted
         */
        if(!$tos) {
            $response->addCommand(
                new InvokeCommand(NULL, 'showErrorCheckmark', ['edit-tos-cb'])
            );
            return $response;
        }
        /**
         * Email verification
         */
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->addCommand(
                new InvokeCommand(NULL, 'showErrorEmail', ['edit-email-input'])
            );
            return $response;
        }
        /**
         * Check if the user has previously voted for the node
         */
        $connection = \Drupal::database();
        $query = $connection->query("SELECT COUNT(id) AS votes FROM likeajax_likes WHERE email = :email AND dyplom = :dyplom;", [
            ':email' => $email,
            ':dyplom' => $dyplom
        ]);
        $result = $query->fetch();
        if(intval($result->votes) > 0) {
            $response->addCommand(
                new HtmlCommand(
                    '.result_message',
                    '<div class="my_top_message error error_duplicate">' . $this->t('Oddałeś/aś już głos na ten dyplom przy pomocy tego emaila!') .'</div>'
                )
            ); 
            return $response;
        }
        /**
         * Generate unique code, send email and add to database
         */
        $code = md5(uniqid());

        if(!($this->sendEmailWithLink($code, $email, $dyplom))) {
            $response->addCommand(
                new HtmlCommand(
                    '.result_message',
                    '<div class="my_top_message error error_sendmail">' . $this->t('Wystąpił błąd przy przesyłaniu maila! Proszę skontaktować się z administratorem.') .'</div>'
                )
            ); 
            return $response;
        }

        $result = $connection->insert('likeajax_likes') //add to database
            ->fields([
                'email' => $email,
                'dyplom' => $dyplom,
                'mailing' => $mailing,
                'verified' => 0,
                'code' => $code,
                'date' => date("Y-m-d H:i:s"),
            ])
            ->execute();
        if(!$result) {
            $response->addCommand(
                new HtmlCommand(
                    '.result_message',
                    '<div class="my_top_message error error_database">' . $this->t('Wystąpił błąd! Proszę skontaktować się z administratorem.') .'</div>'
                )
            ); 
            return $response;
        }

        $response->addCommand(
            new HtmlCommand(
                '.result_message',
                '<div class="my_top_message">' . $this->t('Dziękujemy za oddanie głosu.') .'</div>'
            )
        ); 

        return $response;
    }

    /**
     * (@inheritdoc)
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        drupal_set_message('Dziękuję za oddanie głosu');
    }

    /**
     * Email sending
     */
    private function sendEmailWithLink($code, $email, $id) {
        $node = \Drupal\node\Entity\Node::load($id);
        $title = $node->getTitle();

        $send_mail = new \Drupal\Core\Mail\Plugin\Mail\PhpMail();
        $from = 'tomaszpazera@studiofnc.pl';
        $to = $email;
        $message['headers'] = array(
            'content-type' => 'text/html',
            'MIME-Version' => '1.0',
            'reply-to' => $from,
            'from' => 'sender name <'.$from.'>'
        );
        $message['to'] = $to;
        $message['subject'] = "Potwierdź swój głos - Dyplomy FNC 2018";
        $message['body'] = '
            W celu potwierdzenia swoje głosu na dyplom "' . $title . '" wciśnij
            <a href="http://dyplomy.localhost:8000/?code=' . $code . '">TUTAJ</a>
        ';

        if(!($send_mail->mail($message))) return false;
        return true;
    }
}
