<!--
 ************************************************************
 *  I initially considered obfuscating the code. But then   *
 *  I realized that, that would only make it more readable. *
 *                                                          *
 *                                                  - DJ    *
 ************************************************************	
-->
<html>
	<head>
		<title>Video Chat Client [Coded By : DJ]</title>
		<script src="http://172.16.32.222:1234/socket.io/socket.io.js"></script>
		<script src="scripts/jquery.js"></script>
		<script src="scripts/ZeroClipboard.min.js"></script>
		<script src="scripts/bootstrap.js"></script>
		<script src="scripts/bootbox.js"></script>
		<link href="css/bootstrap.css" rel="stylesheet" media="screen">
		<link rel="shortcut icon" href="img/favicon.ico" />
		<style type="text/css">
			body {
				background-color: black;
				overflow-y: hidden;
			}
			p {
				margin-left: 10px;
				margin-bottom: 2px;
			}
			a {
				cursor:pointer;
			}
			#chat {
				position: absolute;
				background: rgb(15,15,15);
				margin-right: 2px;
				margin-top: 2px;
				height: 320px;
				width: 320px;
				right: 0px;
				top: 0px;
				border: 2px;
				border-color: rgb(61, 85, 109);
				border-style: dotted;
				border-radius: 8px;
				color: rgb(179, 203, 224);
			}
			#messages {	
				height: 290px;
				overflow: auto;
			}
			#boxButton {
			}
			#textBox {
				width: 261px;
				height: 30px;
			}
			#statusBar {
				position: absolute;
				width:100%;
				bottom: 0;
				margin-bottom: 0px;
				text-align: center;
			}
			#buttonInstructions {
				position: absolute;
				bottom: 0;
				margin-bottom: 3;
				left: 0;
				margin-left: 2;
				z-index: 100;
			}
			#buttonFaq {
				position: absolute;
				bottom: 0;
				margin-bottom: 3;
				right: 0;
				margin-right: 2;
				z-index: 100;
			}
			#localVideoDiv {
				background-color: black;
				text-align: center;
				width: 320px;
				height:	240px;
				position: absolute;
				margin-right: 2px;
				z-index: 0;
			}
			#remoteVideoDiv {
				background-color: black;
				text-align: center;
				position:absolute;
				width: 1050px;
				height: 600px;
			}
			#localVideoLoad {
				position: absolute;
				bottom: 38px;
				right: 0px;
				margin-right: 10px;
				margin-bottom: 10px;
				background: url(img/loader.gif) no-repeat center center;
				height: 220px;
				width: 300px;
				border: 2px;
				border-color: rgb(36, 36, 36);
				border-style: dashed;
				border-radius: 8px;
				z-index: 1;
			}
			#remoteVideoLoad {
				position: absolute;
				left: 100;
				top: 5;
				background: url(img/remoteLoader.gif) no-repeat center center;
				height: 590;
				width: 800;
				border: 2;
				border-color: rgb(36, 36, 36);
				border-style: dashed;
				border-radius: 8;
				z-index: 1;
			}
		</style>
		<script type="text/javascript">													
			var localStream;
			var remoteStream;
			var nick;
			var roomId;
			var clientId;
			var pc;
			var started = false;
			var mediaConstraints = {'mandatory': {
            	'OfferToReceiveAudio':true, 
            	'OfferToReceiveVideo':true }};
			function getNick() {
				nick = window.location.href.split('#')[2];
				if(nick == undefined) {
					bootbox.prompt("Enter your Nick", function(result) {
						if(result == '' || result == null)
							getNick();
						else {
							nick = sanitize(result);
							console.log("Nick received : "+nick);
							$('#textBox').focus();
						}
					});
					//$('div.bootbox').css({'width':'300px','position':'fixed','top':'50%','left':'50%','margin':'-100px 0 0 -100px'});
				}
				console.log("Nick : "+nick);
			}
			function getRoom() {
				roomId = window.location.href.split('#')[1];
	            if(roomId == undefined || roomId == "") {
	            	roomId = Math.random().toString(36).substring(2);
	            	window.location.href = window.location.href.split('#')[0]+'#'+roomId;
	            }
			}
			function getUserMedia() {
			  try {
			    navigator.webkitGetUserMedia({'audio':true, 'video':true}, gotStream, gotStreamFailed);
			    console.log("Requested access to local media");
			  } catch (e) {
			    console.error(e, "getUserMedia error");
			  }
			}
			function gotStream(stream) {
				setStatus("Loading...",'warn');
				$('#localVideoLoad').hide();
				$('#localVideoDiv').show();
				setStatusToWaiting();
				var url = webkitURL.createObjectURL(stream);
				document.getElementById("localView").src = url;
				console.log("Successfully gained access to local media : " + url);
				localStream = stream;
				if(clientId == 1)
					doCall();
			}
			function gotStreamFailed(error) {
				console.error(error);
				bootbox.alert("You need to grant me permission to access your WebCam and Microphone :(", function() {
					location.reload();
				});
			}
			function doCall() {
				console.log('Initiating call');
				createPeerConnection();	
				console.log('Adding local stream');			
				pc.addStream(localStream);
				started = true;
				clip.destroy();
				setStatus("Connecting...",'warn');
				pc.createOffer(setLocalAndSendMessage, null, mediaConstraints);
			}
			function doAnswer() {				
				console.log('Replying with an answer : ');
				pc.createAnswer(setLocalAndSendMessage, null, mediaConstraints);
			}
			function setLocalAndSendMessage(sessionDescription) {
				pc.setLocalDescription(sessionDescription);
				sendHandShake(sessionDescription);
			}
			function onIceCandidate(event) {
				if (event.candidate) {
					sendHandShake({type: 'candidate',
				               label: event.candidate.sdpMLineIndex,
				               id: event.candidate.sdpMid,
				               candidate: event.candidate.candidate});
				} else {
				    console.log("End of candidates.");
				}
			}
			function sendHandShake(handshake) {				
				var message = JSON.stringify(handshake);
				console.log('C->S');
				console.log(message);
				sendMessage(message);
			}
			function createPeerConnection() {
				var pc_config = {"iceServers": [{"url": "stun:stun.l.google.com:19302"}]};
				try {
					pc = new webkitRTCPeerConnection(pc_config);
					pc.onicecandidate = onIceCandidate;
					console.log("Created RTCPeerConnnection with configuration : "+ JSON.stringify(pc_config));
				} 
				catch (e) {
					console.log("Failed to create PeerConnection : " + e.message);
				    alert("Cannot create RTCPeerConnection object - WebRTC is not supported by this browser.");
				    return;
				}
				pc.onaddstream = onAddStream;
				pc.onremovestream = onRemoveStream;
			}
			function processSignalingMessage(message) {
				var msg = JSON.parse(message);
				if (msg.type === 'offer') {
					if(!started && !clientId) {
						setStatus("Connecting...",'warn')
						createPeerConnection();
						try {
							pc.addStream(localStream);
						}
						catch(e) {
							gotStreamFailed();
						}
						started = true;
						clip.destroy();
					}
					pc.setRemoteDescription(new RTCSessionDescription(msg));
					doAnswer();
				} else if (msg.type === 'answer' && started) {
					pc.setRemoteDescription(new RTCSessionDescription(msg));
				} else if (msg.type === 'candidate' && started) {
					var candidate = new RTCIceCandidate({sdpMLineIndex:msg.label,
				                           candidate:msg.candidate});
					pc.addIceCandidate(candidate);
				} else if (msg.type === 'bye' && started) {
					bootbox.alert("Call dropped", function() {
						window.location = "./";
						location.reload();
					});
				}
			}

			function onAddStream(event) {
				console.log('Remote stream received');				
				$('#remoteVideoLoad').hide();
				$('#remoteVideoDiv').show();
				var stream = event.stream;
				var url = webkitURL.createObjectURL(stream);
				$("#remoteView")[0].src = url;
				remoteStream = stream;
				setStatus("Coded By : DJ",'success')
			}

			function onRemoveStream(event) {
				$("#remoteView")[0].src = "";
				console.log("Stopped showing remote stream");
			}

			function appendMessage(from, message) {
				$('#messages').append('<p><strong>'+from+'</strong>&nbsp;:&nbsp;'+message+'</p>');
				$("#messages").scrollTop($("#messages")[0].scrollHeight);
			}

			function sanitize(text) {				
				text = text.replace(/\//g, '&#47;');
            	text = text.replace(/>/g,'&gt;');
            	text = text.replace(/</g,'&lt;');
            	return text;
			}

	        function sendChat() {		        	
            	var message = $('#textBox').val();
            	message = sanitize(message);
            	sendMessage(message, "chat");
           		$('#textBox').val('');
           		appendMessage('You', message);
	        }

			function setStatus(status, state) {
				$('#statusBar')[0].innerHTML = '<strong>'+status+'</strong>';
				$('#statusBar').attr({class : 'alert alert-'+state});
			}

			function setStatusToWaiting() {
				$('#remoteVideoLoad').show();
				setStatus("Waiting for someone to join @ "+window.location.href.split('#')[0]+"#"+roomId+" [Click to Copy]", 'info');
				clickToCopy('statusBar');
			}

			function clickToCopy(divId) {
		        ZeroClipboard.setMoviePath("scripts/ZeroClipboard.swf");
				clip = new ZeroClipboard.Client();
				//clip.setHandCursor(true);
				clip.glue(divId);
				clip.setCSSEffects( true );
				clip.setText(window.location.href.split('#')[0]+"#"+roomId);
				clip.addEventListener( 'complete', function(client, text) {
				  bootbox.alert("The URL has been copied to you ClipBoard. Share it with the user you want to chat with.");
				} );
			}
		    function enterFullScreen(elementId) {
		    	var element = document.getElementById(elementId);
		    	element.parentNode.style.bottom = '';
		    	element.parentNode.style.right = '';
		    	element.parentNode.webkitRequestFullScreen();
				element.style.height = screen.height;
				element.style.width = screen.width;
		  	}
			document.addEventListener("webkitfullscreenchange", function () {
	    		if(!document.webkitIsFullScreen) {
	    			var element = document.getElementById('localView');
	    			element.style.height=240;
	    			element.style.width=320;
	    			element.parentNode.style.bottom = 58;
	    			element.parentNode.style.right = 0;
	    			element = document.getElementById('remoteView');
	    			element.style.height=600;
	    			element.style.width=800;
	    		}
				}, false);

			function styleInit () {
            	$("[rel=tooltip]").tooltip();
            	$('#remoteVideoLoad').hide();
            	$('#localVideoDiv').mousedown(function(){ return false; }) // To disable selecting text on double click
            	$('#remoteVideoDiv').mousedown(function(){ return false; })
				var div = document.getElementById('localVideoDiv');
				div.style.bottom = 58;
				div.style.right = 0;
			}

            $(document).ready(function () {
            	styleInit();
				if(navigator.webkitGetUserMedia) {
					getRoom();
	            	var socket = io.connect("http://172.16.32.222:1234");
					console.log("Connecting to the socket...");
	                socket.on('connect', function () {

	                	socket.on('error',function(error) {
							alert(error);
						});	

	                	socket.on('fullroom',function(error) {
							bootbox.alert(error, function() {
								window.location = './##'+nick;
								location.reload();
							});
						});	

						socket.on('clientId', function(id) {
							clientId = id;
							console.log('Client ID : '+clientId);
							getNick();
						});

						socket.on('bye', function (nick) {							
							console.log('Client disconnected. Deleting PeerConnection Object...')
							bootbox.alert(nick+' just hung up.');
							clientId = 0;
							pc.close();
							pc = null;
							started = false;
							setStatusToWaiting();
							onRemoveStream();
						});

						socket.on('receiveMessage', function(from, message){
							console.log('Message Received : '+message);
							appendMessage(from, message);
						});
						
						socket.on('receiveHandshake', function(from, message){
							console.log('S->C :');
							console.log(message);
							try {
								processSignalingMessage(message);
							}
							catch(e) {
								console.error("Error while processing signaling message"+e);
							}
						});
					});
	                signIn = function() {
						socket.emit('signIn', roomId);
					}

					sendMessage = function(message, type) {
		            	var from = nick;
		            	var to = 0;
		            	if(clientId==0) to = 1;
		            	if(type == "chat")
                			socket.emit('sendMessage', roomId, from, to, message);
                		else if(type == "bye")
                			socket.emit('bye', roomId, clientId, to, nick);
                		else
                			socket.emit('handshake', roomId, clientId, to, message);
					}
					signIn();
					setStatus("Allow access to your WebCam and Microphone!", 'error');
					getUserMedia();
                }
				else {
					bootbox.alert("Sorry! Currently, this project can be viewed only on the Chrome web-browser. Kindly download the browser and come back here! :)", function() {
						window.location = "https://www.google.com/intl/en/chrome/browser/beta.html";
					});
				}
				$('#textBox').keydown(function (event) {
		            if(event.keyCode === 13)
		            	sendChat();
		        });

            });
		
			window.onbeforeunload = function () {
				sendMessage('bye','bye');
			}

			$(document).bind('contextmenu', function(e) {
			    if (e.which == 3) {
			        return false;
			    }
			});
        </script>

	</head>

	<body>
		<!-- FAQ -->
			<div id="faq" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-header">
			    	<h3 id="myModalLabel">FAQ</h3>
			  	</div>
			  	<div class="modal-body" style="max-height:300px;">
			    		<b>Q. </b>Dafuq is this?<br>
			    		<b>A. </b><i>Uhmm... Well, this is a simple Video+Text chat application designed to run purely within your web-browser, devoid of any additional addons/plugins.&nbsp;</i><br><br>
			    		<b>Q. </b>Am I logging/recording any of this?<br>
			    		<b>A. </b><i>No. Pinky swear. I've got better things to do, and little disk-space to waste. Further the media streams are transferred P2P and not routed through the server, implying, I have no direct access to them.</i><br><br>
			    		<b>Q. </b>How?<br>
			    		<b>A. </b><i>For those souls who are actually interested :</i><br>
			    				  <i><a href="http://www.webrtc.org/" title="" target="_blank">WebRTC</a> is the underlying technology that has been used here for real-time-communication. Currently <a href="https://www.google.com/intl/en/chrome/browser/beta.html" title="" target="_blank">Chrome</a>&nbsp;is the only web-browser that has implemented this, accounting for the browser restrictions I've&nbsp;levied&nbsp;on the users. Apologies for that.</i><i>The project has been coded purely in JavaScript and HTML5, with some CSS3 blackmagic to make it look pretty (rather, less-ugly). The server-side technologies used include <a href="http://nodejs.org" title="" target="_blank">Node.js</a> and <a href="http://socket.io/" title="" target="_blank">Socket.io</a>.&nbsp;</i><br><br>
			    		<b>Q. </b>Why?<i><br></i>
			    		<b>A. </b><i>The usual reason. I was jobless.</i><br><br>
			    		If you do have more queries/suggestions/criticism/feedback, feel free to leave me a message on 
			    		<a rel="tooltip" data-placement="bottom" title="Nick : DJ">DC</a> /&nbsp;
			    		<a rel="tooltip" data-placement="bottom" title="Handle : vishnumohandas">GTalk</a>&nbsp;/&nbsp;
			    		<a href="http://fb.com/v15hnu" rel="tooltip" data-placement="bottom" title="P.S : I'm socially-awkward." target="_blank">Facebook</a>.<br><br>
			    		Thank you for wasting your time on something I wasted my time on making :)<br><br>
			    		<b>Vishnu | CSE 2k9</b><br>
			  	</div>
			  	<div class="modal-footer">
			    	<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
			  	</div>
			</div>

			<!-- Instructions -->
			<div id="instructions" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
				<div class="modal-header">
			    	<h3 id="myModalLabel">Instructions</h3>
			  	</div>
			  	<div class="modal-body">
			    	<ol>
						<li>Open up the website on the latest version of <a href="https://www.google.com/intl/en/chrome/browser/beta.html" title="" target="_blank">Chrome Beta</a>.</li>
						<li>Sign in with a Username/Nick of your choice (something that your friends may identify you by).</li>
						<li>Click on 'Allow' when Chrome prompts you to give the website access to your Camera and Microphone.</li>
						<li>Once you can see yourself on the bottom-right corner of your screen, share the unique URL of the website that has been generated for you, with the person you want to chat with.</li>
						<li>Once he/she/it completes steps 1 and 2, the media streaming should commence.</li><li>Close the tab to end communication.</li>
					</ol>
			  	</div>
			  	<div class="modal-footer">
			    	<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
			  	</div>
			</div>


			<div id="localVideoLoad"></div>
			<div id="localVideoDiv" display="none">
				<video id="localView"  style="width:320; height:240;" autoplay="autoplay" ondblclick="enterFullScreen('localView')"></video>
			</div>
			<div id="remoteVideoLoad" display="none"></div>
			<div id="remoteVideoDiv" display="none">
				<video id="remoteView" style="width:800; height:600;" autoplay="autoplay" ondblclick="enterFullScreen('remoteView')"></video>
			</div>
			<div id="chat">
	        	<div id="messages">&nbsp;</div>    
	        	<div id="boxButton" class="input-append">
					<input id="textBox" type="text" placeholder="Chat Box"/>
					<button class="btn" type="button" onclick="sendChat()">Send</button>
				</div>
			</div>
			<button id="buttonInstructions" class="btn btn-inverse" type="button" href='#instructions' role='button' class='btn' data-toggle='modal'>Instructions</button>
			<div id="statusBar"></div>
			<button id="buttonFaq" class="btn btn-inverse" type="button" href='#faq' role='button' class='btn' data-toggle='modal'>FAQ</button>
	</body>
</html>