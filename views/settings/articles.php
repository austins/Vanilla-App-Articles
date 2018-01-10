<?php defined('APPLICATION') or exit(); ?>
<h1><?php echo $this->title(); ?></h1>

<?php echo $this->ConfigurationModule->toString(); ?>

<section class="padded">
    <h2><?php echo t('Feedback'); ?></h2>

    <div class="padded clearfix">
        <div class="pull-right"><a
                    href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=72R6B2BUCMH46" target="_blank"><img
                        src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="" style="vertical-align: middle;"/></a>
        </div>

        <?php echo t('Articles.Settings.DonateInfo',
            'If you like this application and would like to support the developer, click the donation button to the right. :)'); ?>
    </div>
</section>
