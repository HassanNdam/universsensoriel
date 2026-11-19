<?php

/**
 * Pont : en-tête de tous les e-mails (remplace emails/email-header.php de WooCommerce).
 *
 * @var string $email_heading
 */
defined('ABSPATH') || exit;

echo \Roots\view('mails.entete', ['titre' => (string) ($email_heading ?? '')] + App\mail_habillage())->render();
