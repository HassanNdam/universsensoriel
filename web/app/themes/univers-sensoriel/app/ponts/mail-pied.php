<?php

/**
 * Pont : pied de tous les e-mails (remplace emails/email-footer.php de WooCommerce).
 */
defined('ABSPATH') || exit;

echo \Roots\view('mails.pied', App\mail_habillage())->render();
