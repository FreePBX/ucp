<?php
global $amp_conf;
?>
<div class="d-flex flex-row">
    <div>
        <div id="footer-message" class="small-text">
            <a href="<?= $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] ?>">
                <img height="15" src="/ucp/assets/images/tango.png" alt="<?= $amp_conf['BRAND_FREEPBX_ALT_FOOT'] ?>">
            </a>
            <?php echo sprintf(_('User Control Panel is released as %s or newer'), '<a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank">AGPLV3</a>') ?>.
            <?php echo 'Copyright 2013-' . $year . ' Sangoma Technologies Inc' ?>.
            <?php echo _('The removal of this copyright notice is stricly prohibited') ?>
        </div>
    </div>
</div>