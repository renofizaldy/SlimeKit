<?php

namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



class Mailer
{
  protected $mail;

  public function __construct(array $config)
  {
    $this->mail = new PHPMailer(true);
    $this->configure($config);
  }

  protected function configure(array $config)
  {
    $this->mail->isSMTP();
    $this->mail->Host       = $config['host'];
    $this->mail->SMTPAuth   = true;
    $this->mail->Username   = $config['username'];
    $this->mail->Password   = $config['password'];
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $this->mail->Port       = $config['port'];

    $this->mail->setFrom($config['from_email'], $config['from_name']);
  }

  public function renderTemplate($templateName, $data = [])
  {
    $path = __DIR__ . '/../views/email/client/' . $templateName . '.php';

    if (!file_exists($path)) {
      throw new \Exception("Template not found: $templateName");
    }

    extract($data);
    ob_start();
    include $path;
    return ob_get_clean();
  }

  public function sendWithTemplate($to, $subject, $templateName, $data = [])
  {
    try {
      $body = $this->renderTemplate($templateName, $data);
      $this->send($to, $subject, $body, true);
    } catch (\Exception $e) {
      // Silently fail - no logging, no return value
      // You might want to add error logging here in production
    }
    // $body = $this->renderTemplate($templateName, $data);
    // $this->send($to, $subject, $body, true);
  }

  public function send($to, $subject, $body, $isHTML = true)
  {
    try {
      $this->mail->clearAllRecipients();
      $this->mail->addAddress($to);
      $this->mail->isHTML($isHTML);
      $this->mail->Subject = $subject;
      $this->mail->Body    = $body;

      return $this->mail->send();
    } catch (Exception $e) {
      return false;
    }
  }
}