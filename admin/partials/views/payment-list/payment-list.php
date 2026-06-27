<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap opwc-admin-page">
    <div class="opwc-payment-list-page">
        <div class="page-header ">
            <h1 class="opwc-page-title"><?php esc_html_e('OwnPay Payment List', 'ownpay-wordpress'); ?></h1>
        </div>
        <div class="page-body">
            <?php $this->render_payment_table() ?>
        </div>
    </div>
</div>