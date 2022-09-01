<div class="modal fade" id="modal-policies" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #FF7171">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title text-center" id="myModalLabel">
                    <strong><?php echo _('Password does not match security policy.')?></strong>
                </h4>
            </div>
            <div class="modal-body" style="background-color: #FCF8E3"></div>
            <div class="modal-footer" style="background-color: #DCECFE">
                <button type="button" class="btn btn-primary active" data-dismiss="modal">
					<?php echo _('Close')?>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div id="login-window" class="col-md-4 col-md-offset-3 col-sm-6 col-sm-offset-2 col-xs-8 col-xs-offset-1" style="<?php echo (!empty($error_warning) || !empty($error_danger)) ? 'height: 300px;' : ''?>">
        <div id="">
            <form id="frm-login" method="POST" action="?display=dashboard">
                <input type="hidden" name="token" value="<?php echo $token?>">
                <input type="hidden" name="ftoken" value="<?php echo $ftoken?>">
                <h2 class="header text-center"><?php echo _('User Control Panel')?></h2>
                <?php if(!empty($error_warning)) {?>
                <div class="alert alert-warning"><?php echo $error_warning?></div>
                <?php } ?>
                <?php if(!empty($error_danger)) {?>
                <div class="alert alert-danger"><?php echo $error_danger?></div>
                <?php } ?>
                <div class="alert alert-warning jsalert" style="display:none;"></div>
                <div id="error-msg" class="alert alert-danger" style="display:none"></div>
                <div class="lhide">
                    <div class="input-group input-margin" style="padding: 0px 15px;">
                        <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Username" autocapitalize="off" autocorrect="off" value="<?php echo $username?>">
                    </div>
                </div>
                <div class="lhide">
                    <div class="input-group input-margin" style="padding: 0px 15px;">
                        <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Password" autocapitalize="off" autocorrect="off">
                    </div>
                </div>
                <div class="lshow reset-title">
                    <?php echo sprintf(_("Password Reset for: %s"),$username)?>
                </div>
                <div class="lshow">
                    <div class="input-group input-margin" style="padding: 0px 15px;">
                        <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
                        <input type="password" name="npass1" class="form-control" placeholder="New Password" autocapitalize="off" autocorrect="off">
                    </div>
                </div>
                <div class="lshow">
                    <div class="input-group input-margin" style="padding: 0px 15px;">
                        <span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
                        <input type="password" name="npass2" class="form-control" placeholder="Confirm New Password" autocapitalize="off" autocorrect="off">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <table class="extras">
                            <tr class="action-switch">
                                <td colspan="2" class="lshow"><span data-show="lhide" data-hide="lshow" id="switch-login"><i class="fa fa-sign-in"></i> <?php echo _('Login')?></span>
                                </td>
                            </tr>
                            <tr class="lhide remember-me">
                                <td class="text"><?php echo _('Remember Me')?></td>
                                <td id="rm-checkbox" class="checkbox-c">
                                    <div class="onoffswitch">
                                        <input type="checkbox" name="rememberme" class="onoffswitch-checkbox"
                                            id="rememberme">
                                        <label class="onoffswitch-label" for="rememberme">
                                            <div class="onoffswitch-inner"></div>
                                            <div class="onoffswitch-switch"></div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="button-row">
                                        <?php 
									if (\FreePBX::Modules()->checkStatus('pbxmfa')) { ?>
										<button type="button" id="btn-mfalogin" class="btn btn-default lhide" ><?php echo _('Login')?></button>
									<?php } else { ?>
									<button type="submit" id="btn-login" class="btn btn-default lhide" disabled><?php echo _('Loading...')?></button>
								<?php }  ?>
                                    <button type="button" id="btn-forgot"
                                        class="btn btn-default lshow"><?php echo _('Reset Password')?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </form>
            <span style="color: #dcecfe;"><?php echo session_id()?></span>
        </div>
    </div>
	<?php 
		if (\FreePBX::Modules()->checkStatus('pbxmfa')) { 
			echo \FreePBX::Pbxmfa()->otpPage('ucp'); 
		} 
	?>
</div>