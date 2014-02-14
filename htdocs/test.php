<!DOCTYPE html>
<html>
	<head>
		<title>User Control Panel</title>

		<link href="assets/css/compiled/lessphp_a2e1ef11b064ee138b229a4e39ef2e85ee95b806.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="assets/framework/css/font-awesome.min.css">
		<link href="assets/css/miniplayer.css" rel="stylesheet" type="text/css">
		<link href="assets/framework/css/jquery-ui.css?load_version=12.0.1alpha23" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="assets/framework/js/jquery-1.11.0-beta2.min.js"></script>
		<script type="text/javascript" src="assets/framework/js/jquery-migrate-1.2.1.js"></script>
		<link href="assets/css/compiled/lessphp_93f083add3e6b1f3523917173d570cef882537d2.css" rel="stylesheet" type="text/css">
		<link href="assets/css/jplayer.blue.monday.css" rel="stylesheet" type="text/css">
		<script>

			$(document).ready(function() {
			    $("#jquery_jplayer_1").jPlayer({
			        ready: function(event) {
			            $(this).jPlayer("setMedia", {
			                wav: "http://freepbxdev1.schmoozecom.net/ucp/test.wav"
			            });
			        },
			        swfPath: "assets/js",
			        supplied: "wav",
					warningAlerts: false
			    });
			});     
		</script>
		<style>
			/*++++++++++++++++++++++++++++++++++++++++++++++++++
			Copyright (c) 2001-2014. Matteo Bicocchi (Pupunzi);
			http://pupunzi.com/mb.components/mb.miniAudioPlayer/demo/skinMaker.html

			MAP custom skin: freepbx
			borderRadius: 7
			background: #000000
			icons: rgba(163, 160, 160, 1)
			border: rgba(163, 160, 160, 1)
			borderLeft: #333333
			borderRight: #000000
			mute: #cccccc
			download: rgba(5, 5, 5, 1)
			downloadHover: rgba(163, 160, 160, 1)
			++++++++++++++++++++++++++++++++++++++++++++++++++*/

			/* Older browser (IE8) 
			   not supporting rgba() */ 
			.mbMiniPlayer.freepbx .map_download{color: #050505;}
			.mbMiniPlayer.freepbx .map_download:hover{color: #a3a0a0;}
			.mbMiniPlayer.freepbx table span{color: #a3a0a0;}
			.mbMiniPlayer.freepbx table {border: 1px solid #a3a0a0 !important;}

			/*++++++++++++++++++++++++++++++++++++++++++++++++*/

			.mbMiniPlayer.freepbx table{background-color:transparent; border-radius:7px !important;}
			.mbMiniPlayer.freepbx.shadow table{box-shadow:0 0 3px #000000;}
			.mbMiniPlayer.freepbx table span{background-color:#000000;}
			.mbMiniPlayer.freepbx table span.map_play{border-left:1px solid #333333; border-radius:0 6px 6px 0 !important;}
			.mbMiniPlayer.freepbx table span.map_volume{border-right:1px solid #000000; border-radius:6px 0 0 6px !important;}
			.mbMiniPlayer.freepbx table span.map_volume.mute{color: #cccccc;}
			.mbMiniPlayer.freepbx .map_download{color: rgba(5, 5, 5, 1);}
			.mbMiniPlayer.freepbx .map_download:hover{color: rgba(163, 160, 160, 1);}
			.mbMiniPlayer.freepbx table span{color: rgba(163, 160, 160, 1);text-shadow: 1px -1px 1px #000!important;}
			.mbMiniPlayer.freepbx table span{color: rgba(163, 160, 160, 1);}
			.mbMiniPlayer.freepbx table {border: 1px solid rgba(163, 160, 160, 1) !important;}
			.mbMiniPlayer.freepbx table span.map_title{color: #000; text-shadow:none!important}
			/*++++++++++++++++++++++++++++++++++++++++++++++++*/
		</style>

		<script type="text/javascript" src="assets/framework/js/bootstrap-3.0.2.custom.min.js"></script>
		<script type="text/javascript" src="assets/framework/js/jquery-ui-1.10.3.custom.min.js"></script>
		<script type="text/javascript" src="assets/framework/js/jquery.cookie.js?load_version=12.0.1alpha23"></script>
		<script type="text/javascript" src="assets/js/jquery.form.min.js"></script>
		<script type="text/javascript" src="assets/js/jquery.jplayer.min.js"></script>
		<script type="text/javascript" src="assets/js/jquery.mb.miniPlayer.js"></script>
		<script type="text/javascript" src="assets/js/quo.js"></script>
		<script type="text/javascript" src="assets/js/purl.js"></script>
		<script type="text/javascript" src="assets/js/modernizr.js"></script>
		<script type="text/javascript" src="assets/js/fastclick.js"></script>
		<script type="text/javascript" src="assets/js/jquery.pjax.js"></script>
		<script type="text/javascript" src="assets/js/ucp.js"></script>
	</head>
	<body>
		<div id="jquery_jplayer_1" class="jp-jplayer"></div>

		<div id="jp_container_1" class="jp-audio">
		    <div class="jp-type-single">
		        <div class="jp-gui jp-interface">
		            <ul class="jp-controls">
                
		                <!-- comment out any of the following <li>s to remove these buttons -->
                
		                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
		                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
		                <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
		                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
		                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
		                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
		            </ul>
            
		            <!-- you can comment out any of the following <div>s too -->
            
		            <div class="jp-progress">
		                <div class="jp-seek-bar">
		                    <div class="jp-play-bar"></div>
		                </div>
		            </div>
		            <div class="jp-volume-bar">
		                <div class="jp-volume-bar-value"></div>
		            </div>
		            <div class="jp-current-time"></div>
		            <div class="jp-duration"></div>                   
		        </div>
		        <div class="jp-title">
		            <ul>
		                <li>Cro Magnon Man</li>
		            </ul>
		        </div>
		        <div class="jp-no-solution">
		            <span>Update Required</span>
		            To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
		        </div>
		    </div>
		</div>
	</body>
</html>