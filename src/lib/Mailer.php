<?php

namespace App\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

class Mailer
{
  protected $mail;

  public function __construct()
  {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->safeLoad();

    $this->mail = new PHPMailer(true);
    $this->configure();
  }

  protected function configure()
  {
    $this->mail->isSMTP();
    $this->mail->Host       = $_ENV['SMTP_HOST'];
    $this->mail->SMTPAuth   = true;
    $this->mail->Username   = $_ENV['SMTP_USER'];
    $this->mail->Password   = $_ENV['SMTP_PASS'];
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $this->mail->Port       = $_ENV['SMTP_PORT'];

    $this->mail->setFrom('info@lolitamakeup.id', $_ENV['MAIL_FROM_NAME']);
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