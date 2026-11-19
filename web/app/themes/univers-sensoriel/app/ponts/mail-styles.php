<?php

/**
 * Pont : styles de tous les e-mails (remplace emails/email-styles.php de WooCommerce).
 * WooCommerce recopie ensuite ces règles dans les balises.
 */
defined('ABSPATH') || exit;

echo App\mail_styles();
