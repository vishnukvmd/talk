var io = require('socket.io').listen(1234);
io.set('log level', 1);
var room = {};
io.sockets.on('connection', function (socket) {
    var address = socket.handshake.address;

	socket.on('signIn', function (roomId) {
		try {
			var clientId;
			if(room[roomId] == undefined || room[roomId].client[0] == undefined) {	// First user to enter the room
				room[roomId] = {client : [], roomFull : 0};
				room[roomId].client[0] = socket;
				clientId = 0;
				time = new Date();
				require('util').log("SignIn @ " + roomId + " : #" + clientId);				
  				require('util').log("IP : " + address.address + ":" + address.port);
			}
			else {
				if(room[roomId].client.length != 2) {	// Second user to enter the room
					room[roomId].client[1] = socket;
					clientId = 1;
					require('util').log("SignIn @ " + roomId + " : #" + clientId);				
	  				require('util').log("IP : " + address.address + ":" + address.port);
				}
				else {	// 2 users already in the room
					require('util').log("RoomFull : "+roomId);
					socket.emit('fullroom','Sorry, this room is already full! Redirecting you into a new room...');
					room[roomId].roomFull = 1;
				}
			}
			if(!room[roomId].roomFull) {
				socket.emit('clientId',clientId);
			}
		}
		catch(e) {
			require('util').log("SignInError : "+ roomId + " | " + address.address);
		}
	});

	socket.on('sendMessage', function (roomId, from, to, message) {
		try {
			room[roomId].client[to].emit('receiveMessage',from, message);
			require('util').log("Message @ "+roomId+" - "+from+" -> "+to+" : "+message);
		}
		catch(e) {
			require('util').log("MessageFail @ "+roomId+" - "+from+" -> "+to+" : "+message);
		}
	});

	socket.on('handshake', function (roomId, from, to, message) {
		try {
			room[roomId].client[to].emit('receiveHandshake',from, message);
			//require('util').log("Handshake Forwarded from "+from+" to "+to);
		}
		catch(e) {
			require('util').log("HandShake Fail @ "+roomId+" - "+from+" -> "+to+" : "+message);
		}
	});

	socket.on('bye', function (roomId, from, to, nick) {
		try {
			require('util').log(roomId,from,to,nick);
			room[roomId].client.splice(from, 1);
			require('util').log(address.address+' disconnected from Room : '+roomId);		
			if(room[roomId].client[0] != undefined) room[roomId].client[0].emit('bye', nick);
		}
		catch(e) {
			require('util').log(roomId,from,to,nick);
			require('util').log("Error while disconnecting client.");
		}
	});

});