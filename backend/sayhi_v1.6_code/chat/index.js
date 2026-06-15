const express = require('express');
const app = express();
var fs = require('fs');
const http = require('http');
const https = require('https');
var config = require("./config.json");
var server = http.createServer(app);
var conn = require('./core/db.js');
if (config.sslCertificatePath.isSsl) {
  var options = {
    key: fs.readFileSync(config.sslCertificatePath.key),
    cert: fs.readFileSync(config.sslCertificatePath.cert)
  };
  server = https.createServer(options, app);
}
const { Server } = require("socket.io");
const io = new Server(server);
const admin = require('firebase-admin');
const crypto = require("crypto-js")

const { RtcTokenBuilder, RtmTokenBuilder, RtcRole, RtmRole } = require('agora-access-token')
var apn = require("apn");
const { v4: uuidv4 } = require('uuid')


/// START chat message user status
const STATUS_UNRECEIVED = 1;
const STATUS_RECEIVED = 2;
const STATUS_READ = 3;
const STATUS_DELETED = 0;

/// END chat message user status

/// START main chat message status
const CURRENT_STATUS_SEND = 1;
const CURRENT_STATUS_DELIVERED = 2;
const CURRENT_STATUS_SEEN = 3;
/// END chat message user status


/// START chatt room user
const CHAT_ROOM_USER_STATUS_ACTIVE = 10;
const CHAT_ROOM_USER_STATUS_REMOVED = 2;
const CHAT_ROOM_USER_STATUS_LEFT = 3;

/// END chat message user status

const CHAT_ROOM_TYPE_PRIVATE = 1;
const CHAT_ROOM_TYPE_GROUP = 2;
const CHAT_ROOM_TYPE_OPEN = 3;

const COMMON_NO = 0;
const COMMON_YES = 1;


const STATUS_LIVE_CALL_ONGOING = 1;
const STATUS_LIVE_CALL_COMPLETED = 2;



const STATUS_LIVE_CALL_BATTLE_PENDING = 1;
const STATUS_LIVE_CALL_BATTLE_ACCEPTED = 2;
const STATUS_LIVE_CALL_BATTLE_REJECTED = 3;
const STATUS_LIVE_CALL_BATTLE_ONGOING = 4;
const STATUS_LIVE_CALL_BATTLE_CANCELLED = 5;
const STATUS_LIVE_CALL_BATTLE_COMPLETED = 10;

const STATUS_LIVE_CALL_USER_ROLE_SUPER_HOST = 1;
const STATUS_LIVE_CALL_USER_ROLE_USER = 2;
const STATUS_LIVE_CALL_USER_ROLE_MODERATOR = 2;

const STATUS_LIVE_CALL_BAN_TYPE_WHOLE_CALL = 1;
const STATUS_LIVE_CALL_BAN_TYPE_TIME_PERIOD = 2;


const STATUS_LIVE_CALL_ACTION_TYPE_BAN = 1;
const STATUS_LIVE_CALL_ACTION_TYPE_UNBAN = 2;
const STATUS_LIVE_CALL_ACTION_TYPE_ROLE_UPDATE = 3;



var STORAGE_URL = config.storageUrl;
conn.connectDB(server).then(function (db) {

  serviceAccount = require("./serviceAccountKey.json");
  admin.initializeApp({
    credential: admin.credential.cert(serviceAccount),
    databaseURL: config.pushNotification.databaseURL
  });

  process.on('warning', e => console.warn(e.stack));
  app.get('/', (req, res) => {
    res.sendFile(__dirname + '/index.html');
  });


 


  //sockets = [];
  //users = {};

  io.on('connection', function (socket) {
    console.log('A user connected : ', socket.id);
    socket.on("login", (data) => {
      console.log('UserLogin', data.userId, socket.id);
      console.log(data);
      //users[socket.id] = data.userId;
      socket.username = data.username;
      socket.userId = data.userId;
      


      // #region login to user in all room

      let currentTime = Math.round((new Date()).getTime() / 1000);

      let query = "update user set socket_id='" + socket.id + "',chat_last_time_online='" + currentTime + "',is_chat_user_online='" + COMMON_YES + "'  where id = " + data.userId;
      db.query(query, function (err, results, fields) {
        if (err) throw err;

        // db.end();
        let query = "SELECT * FROM chat_room_user where user_id =" + data.userId;
        db.query(query, function (err, results, fields) {
          if (err) throw err;
          results.forEach(element => {


            socket.join(element.room_id);

            socket.to(element.room_id).emit('userOnline', {
              username: socket.username,
              userId: socket.userId,
              room: element.room_id
            });


          });

        });

      });
      //#endregion

      // #region send offline message to current login user
      var sql = "SELECT chat_message_user.id as chat_message_user_id ,chat_message_user.chat_message_id,chat_message_user.status, user.socket_id, sender_user.id as sender_user_id,sender_user.username, sender_user.socket_id as sender_socket_id, chat_message.*   FROM chat_message_user left join chat_message on chat_message.id = chat_message_user.chat_message_id LEFT JOIN user on chat_message_user.user_id = user.id LEFT JOIN user as sender_user on chat_message.created_by = sender_user.id  where chat_message_user.user_id =" + data.userId + " and chat_message_user.status=" + STATUS_UNRECEIVED + " and chat_message.status <>" + STATUS_DELETED;
      db.query(sql, function (err, results, fields) {
        if (err) throw err;

        if (results.length) {
          results.forEach(element => {

            var isUserNotify = COMMON_NO;
            var chatMessageUserStatus = STATUS_RECEIVED;
            if (element.delete_time > 0 && currentTime > element.delete_time) { //START  message should be deleted not deliver to user


              /// delete message
              db.query("update chat_message set status='" + STATUS_DELETED + "' where id =" + element.id, function (err, results, fields) {

                //console.log(results) // message deleted
                db.query("update chat_message_user set status='" + STATUS_DELETED + "',is_user_notify=1 where chat_message_id =" + element.id, function (err, results, fields) {
                  //console.log(results) // message deleted
                })
              })

            } else {


              var username = element.username
              io.to(element.socket_id).emit('sendMessage', {
                id: element.id,
                localMessageId: element.local_message_id,
                room: element.room_id,
                messageType: element.type,
                message: element.message,
                replied_on_message: element.replied_on_message,
                created_at: element.created_at,
                created_by: element.created_by,
                username: username,
                is_encrypted: element.is_encrypted,
                chat_version: element.chat_version
              });

            
              var resultSocket = io.sockets.sockets.get(element.sender_socket_id);
              if (resultSocket) {
                io.to(element.sender_socket_id).emit('updateMessageCurrentStatusUser', {
                  id: element.id,
                  status: STATUS_RECEIVED,
                  messageId: element.id,
                  localMessageId: element.local_message_id,
                  room: element.room_id,
                  userId: socket.userId
                });
                isUserNotify = COMMON_YES;
              }

              db.query("update chat_message_user set status='" + STATUS_RECEIVED + "', is_user_notify='" + isUserNotify + "' where id =" + element.chat_message_user_id, function (err, results, fields) {
                //console.log(results)
              })


            } // END  message should be deleted not deliver to user


            //  }
          });

         
        }

      });

      //#endregion

      // #region send offline deleted  message to user inform
      var sql = "SELECT chat_message_user.id as chat_message_user_id ,chat_message_user.chat_message_id,chat_message_user.status, user.socket_id, sender_user.id as sender_user_id,sender_user.username, sender_user.socket_id as sender_socket_id, chat_message.*   FROM chat_message_user left join chat_message on chat_message.id = chat_message_user.chat_message_id LEFT JOIN user on chat_message_user.user_id = user.id LEFT JOIN user as sender_user on chat_message.created_by = sender_user.id  where chat_message_user.user_id =" + data.userId + " and chat_message_user.is_user_notify=0  and chat_message_user.status =" + STATUS_DELETED;
      db.query(sql, function (err, results, fields) {
        if (err) throw err;
        console.log(results);

        if (results.length) {
          results.forEach(element => {

            var username = element.username
            console.log('inner' + username);
            var sendData = {
              id: element.chat_message_id,
              username: username,
              room: element.room_id,
              deleteScope: 2
            }
            socket.emit('deleteMessage', sendData);
            var isUserNotify = 1;
            db.query("update chat_message_user set is_user_notify='" + isUserNotify + "' where id =" + element.chat_message_user_id, function (err, results, fields) {
              //console.log(results)
            })
            //  }
          });

         
        }

      });

      //#endregion



      // #region inform user their message current status that are update during you are offline 


      var sql = "SELECT chat_message_user.id as chat_message_user_id, chat_message_user.user_id,chat_message_user.chat_message_id,chat_message_user.status, chat_message.local_message_id, chat_message.room_id FROM chat_message_user left join chat_message on chat_message.id = chat_message_user.chat_message_id where chat_message.created_by = " + data.userId + " and chat_message_user.is_user_notify=" + COMMON_NO + " and chat_message.status <>" + STATUS_DELETED;;
      db.query(sql, function (err, results, fields) {
        if (err) throw err;
        var ids = [];

        results.forEach(element => {
          ids.push(element.chat_message_user_id);
          socket.emit('updateMessageCurrentStatusUser', {
            id: element.id,
            status: element.status,
            messageId: element.chat_message_id,
            localMessageId: element.local_message_id,
            room: element.room_id,
            userId: element.user_id
          });

        })

        if (ids.length) {
          db.query("update chat_message_user set is_user_notify='" + COMMON_YES + "' where id IN(" + ids + ")", function (err, results, fields) {
            //console.log(results)
          })

        }


      })


      //#endregion



    });
    // sockets.push(socket);

    //console.log(sockets)
    socket.on('addUser', (data) => {

      //join the registered user 
      console.log(data);
      var room = data.room;
      usernameActiondBy = socket.username;

      var loggedUserId = socket.userId;

      var currentTime = Math.round((new Date()).getTime() / 1000);




      var sql = "SELECT * FROM chat_room where id = " + db.escape(room);
      // console.log(sql)

      db.query(sql, function (err, resultsRoom, fields) {
        if (err) throw err;


        var roomCreatedBy = 0;
        var roomType = 0;
        if (resultsRoom.length) {
          roomCreatedBy = resultsRoom[0].created_by;
          roomType = resultsRoom[0].type;

        }

        db.query("SELECT id,username,socket_id FROM user where id IN(" + data.userId + ")", function (err, results, fields) {
          if (err) throw err;

          results.forEach(user => {

            db.query("SELECT * FROM chat_room_user where user_id =" + user.id + " and room_id =" + room + " and status =10", function (err, result, fields) {

              if (!result.length) {
                var isAdmin = 0;
                if (user.id == roomCreatedBy) {
                  var isAdmin = 1;
                }

                var sql = "INSERT INTO chat_room_user (room_id, user_id,is_admin,created_at,created_by) VALUES (" + db.escape(room) + "," + db.escape(user.id) + "," + db.escape(isAdmin) + "," + db.escape(currentTime) + "," + db.escape(loggedUserId) + " )";
                //console.log(sql);
                db.query(sql, function (err, resultInsert) {
                  if (err) throw err;

                  //send notififiction to all room user that someone is added

                  if (roomType == CHAT_ROOM_TYPE_GROUP || roomType == CHAT_ROOM_TYPE_OPEN) { // send only when group chat room
                    var sendData = {
                      userId: user.id,
                      username: user.username,
                      room: room,
                      usernameActiondBy: usernameActiondBy,
                      userIdActiondBy: loggedUserId,
                      created_at: currentTime
                    }
                    //console.log(sendData)

                    io.to(room).emit('addUser', sendData);
                  }




                  var socketId = user.socket_id;
                  //var resultSocket = sockets[socketId];
                  var resultSocket = io.sockets.sockets.get(socketId);
                  if (resultSocket) { // if user online 

                    resultSocket.join(room);
                  }

                });

              }
            });

          });

        })
      })
      //socket.broadcast.to(data.receiverSocketId).emit('sendMessage', 'for your eyes only');
      //   socket.broadcast.to(data.receiverSocketId).join(data.room);
    })

    socket.on('leftRoom', (data) => {
      console.log('left room')
      username = socket.username;
      var userId = socket.userId;
      var room = data.room;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      var sql = "update chat_room_user set status='" + CHAT_ROOM_USER_STATUS_LEFT + "' where user_id = " + db.escape(userId) + " and room_id = " + db.escape(room);
      db.query(sql, function (err, results, fields) {
        var sendData = {
          userId: userId,
          username: username,
          room: room,
          created_at: currentTime
        }
        //console.log(sendData)

        io.to(room).emit('leftRoom', sendData);
        socket.leave(room);

      })
    });
    socket.on('removeUserFromRoom', (data) => {
      console.log('removeUserFromRoom')
      usernameActiondBy = socket.username;
      var userIdActiondBy = socket.userId;
      var userId = data.userId;
      var room = data.room;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, resultsRemoved, fields) {
        //console.log(results[0].created_by);

        if (resultsRemoved) {
          if (resultsRemoved.length) {
            var username = resultsRemoved[0].username;

            var sql = "update chat_room_user set status='" + CHAT_ROOM_USER_STATUS_REMOVED + "' where user_id = " + db.escape(userId) + " and room_id = " + db.escape(room);
            db.query(sql, function (err, results, fields) {
              var sendData = {
                userId: userId,
                username: username,
                room: room,
                usernameActiondBy: usernameActiondBy,
                userIdActiondBy: userIdActiondBy,
                created_at: currentTime
              }
              //console.log(sendData)

              io.to(room).emit('removeUserFromRoom', sendData);

              var socketId = resultsRemoved[0].socket_id;
              //var resultSocket = sockets[socketId];
              var resultSocket = io.sockets.sockets.get(socketId);

              if (resultSocket) { // if user online 
                resultSocket.leave(room);
              }


            })



          }

        }
      })


    });

    socket.on('makeRoomAdmin', (data) => {
      console.log('makeRoomAdmin')
      usernameActiondBy = socket.username;
      var userIdActiondBy = socket.userId;
      var userId = data.userId;
      var room = data.room;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, resultsRemoved, fields) {
        //console.log(results[0].created_by);

        if (resultsRemoved) {
          if (resultsRemoved.length) {
            var username = resultsRemoved[0].username;

            var sql = "update chat_room_user set is_admin=1 where user_id = " + db.escape(userId) + " and room_id = " + db.escape(room);
            db.query(sql, function (err, results, fields) {
              var sendData = {
                userId: userId,
                username: username,
                room: room,
                usernameActiondBy: usernameActiondBy,
                userIdActiondBy: userIdActiondBy,
                created_at: currentTime
              }
              io.to(room).emit('makeRoomAdmin', sendData);
            })

          }

        }
      })


    });

    socket.on('removeRoomAdmin', (data) => {
      console.log('removeRoomAdmin')
      usernameActiondBy = socket.username;
      var userIdActiondBy = socket.userId;
      var userId = data.userId;
      var room = data.room;
      var currentTime = Math.round((new Date()).getTime() / 1000);
      db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, resultsRemoved, fields) {

        if (resultsRemoved) {
          if (resultsRemoved.length) {
            var username = resultsRemoved[0].username;

            var sql = "update chat_room_user set is_admin=0 where user_id = " + db.escape(userId) + " and room_id = " + db.escape(room);
            db.query(sql, function (err, results, fields) {
              var sendData = {
                userId: userId,
                username: username,
                room: room,
                usernameActiondBy: usernameActiondBy,
                userIdActiondBy: userIdActiondBy,
                created_at: currentTime
              }
              io.to(room).emit('removeRoomAdmin', sendData);
            })

          }

        }
      })


    });

    socket.on('updateChatAccessGroup', (data) => {
      console.log('updateChatAccessGroup')
      usernameActiondBy = socket.username;
      var userIdActiondBy = socket.userId;
      var room = data.room;
      var chatAccessGroup = data.chatAccessGroup;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      var sql = "update chat_room set chat_access_group=" + db.escape(chatAccessGroup) + " where  id = " + db.escape(room);
      db.query(sql, function (err, results, fields) {
        var sendData = {
          room: room,
          usernameActiondBy: usernameActiondBy,
          userIdActiondBy: userIdActiondBy,
          chatAccessGroup: chatAccessGroup,
          created_at: currentTime
        }
        io.to(room).emit('updateChatAccessGroup', sendData);
      })

    });


    /*
   socket.on('join', (data ) => {
     // console.log(data)
      socket.join(data.room);
   
    })
    */


    socket.on('disconnect', () => {


      try {

        //  delete users[socket.id]; 
        let username = socket.username;
        let userId = socket.userId;

        /// console.log(socket)
        console.log('disconnect fire', userId, socket.id);
        // if (sockets) {


        if (socket.userId) {

          //#region  sent message to all room of user offline  

          let currentTime = Math.round((new Date()).getTime() / 1000);
          let query = "update user set socket_id=NULL,chat_last_time_online='" + currentTime + "',is_chat_user_online='" + COMMON_NO + "'  where id = " + db.escape(socket.userId);
          db.query(query, function (err, results, fields) {
            //console.log(results);
          });



          query = "SELECT * FROM chat_room_user where user_id =" + socket.userId;
          db.query(query, function (err, results, fields) {
            if (err) throw err;
            results.forEach(element => {

              console.log('userOffline', userId);

              socket.to(element.room_id).emit('userOffline', {
                username: username,
                userId: userId,
                room: element.room_id
              });


            });

          });
          //#endregion


          //#region  live call end if user is ongoing live on call
          var inputData = {
            userId: userId
          }
          endLiveCallIfExist(socket, inputData);

          


          //#endregion


          //#region  remove user from viewing live tv list


          var sql = "delete from live_tv_viewer  where user_id = " + db.escape(userId);

          db.query(sql, function (err, results, fields) {
            //console.log('remove from live tv viewer list')

          })
          //#endregion


          //#region  remove user from viewing live call list
          var sql = "delete from live_call_viewer  where is_ban=0 and user_id = " + db.escape(userId);
          db.query(sql, function (err, results, fields) {
            //console.log('remove from live call viewer list')
          })
          //#endregion


        }

        //delete sockets[socket.id];
        //  }
        /// for kill event
        socket.disconnect();
        socket.removeAllListeners();
        //socket = null; //this


        console.log('user disconnected');

      } catch (err) {
        console.log(err);
        console.log('user disconnected errrRRR');

      }
    });


    socket.on('sendMessage', async (data) => {
      //  console.log('message: ' + data);

      // data = {userId: 2, localMessageId: "TRPPSIEKLWii8YBnRc4f6vyg3", is_encrypted: 1, messageType: 9, message: "U2FsdGVkX19xQ4+tXFlqHEGc4JrqOFPUObmXPvC4SQuEts/BnD0ZZAhAVW8hpEaqduwuEvmE+YzTPIwEZ0yFiZZOsb5mD1jh6s4lRy0fHiP+du/qXJzZ0zFNL0q+NooZ", replied_on_message: "U2FsdGVkX18frCOBtD+eu3YiuqB0qb5LicUTMClQ/QBC2cPP/hIcfwRKEpil0qiAZl7zBUq5Gpa27BohkUSZ5iFvjyaclYv8F36nSB6a++OGLxXAtoB3oN5lfFkTkTyrlNVjrjoIf8mzX7fCrT8bwNLWULM3RzdBxRrxVRR7r4hFO2ohQoj5Z0KJ4S47KCBJbG8z8uJ2LybRguNs8n+NohJ6KO+kVTE0v+lDQpvvpKU4roaew6cGVdzzmbKWa4LAmx9ppq62fb4QA2E1MJ/NFcpWwgNfgj4zNPlIwIBb3TBCxQMbxJsBEi2zLZVb4izegxdjvLsuQ5Lc7aIFgcJ0eDhE1an/WWmbKn7IhgPOexrDTYl6P+fE9CU99HFSpZp37IzSmOplME//P8lEtNpaNn3HdziCwb2a8iPuDWEx6p4ImPzfDUsszlc+go5qUlV27GEeXEBelChFmhZScKjRFKtF70Vnv0jeCPyA9ol3Ycg=", chat_version: 1, room: 2, created_by: 2, created_at: 1672672152,deleteAfter: null};

      try {
        data.username = socket.username;
        //data.created_at = new Date().getTime();
        data.created_at = Math.round((new Date()).getTime() / 1000);
        data.created_by = data.userId;
        var room = data.room;
        var userId = data.userId;

        //#region  validation for block user
        var query = "SELECT  chat_room.type,chat_room_user.id,chat_room_user.status,chat_room_user.user_id,user.device_token,user.socket_id FROM chat_room_user left join user on chat_room_user.user_id = user.id left join chat_room on chat_room_user.room_id=chat_room.id  where chat_room_user.room_id =" + room;
        var resultsRoom = await getDbData(query);
        if (resultsRoom.length) {
          if (resultsRoom[0].type == 1) { //private room
            for await (const chatRoomUser of resultsRoom) {
              var query = "SELECT * from blocked_user where blocked_user_id =" + userId + " and  user_id =" + chatRoomUser.user_id;
              var blockResultUser = await getDbData(query);
              if (blockResultUser.length) {
                return false; // stop going from farwar
              }
            }
          }

        }
        //#endregion
        var delete_time = 0
        if (data.deleteAfter > 0) {

          delete_time = data.created_at + data.deleteAfter;
        }
        //  console.log(data.created_at,'deleteTime : ',delete_time);
        var sql = "INSERT INTO chat_message (local_message_id,room_id, type, message, replied_on_message, is_encrypted, chat_version, created_by, created_at,delete_time) VALUES (" + db.escape(data.localMessageId) + "," + db.escape(data.room) + "," + db.escape(data.messageType) + "," + db.escape(data.message) + "," + db.escape(data.replied_on_message) + "," + db.escape(COMMON_YES) + "," + db.escape(data.chat_version) + "," + db.escape(data.userId) + "," + db.escape(data.created_at) + "," + db.escape(delete_time) + " )";
        db.query(sql, function (err, results) {
          if (err) throw err;
          var id = results.insertId;
          data.id = id;
          // console.log(data)

          socket.to(room).emit('sendMessage', data);

          //socket.emit('messageStatusSend', data );

          socket.emit('updateMessageCurrentStatus', {
            current_status: CURRENT_STATUS_SEND,
            id: id,
            localMessageId: data.localMessageId,
            room: room,
            created_at: data.created_at,
            userId: socket.userId,
            receiver_id: socket.userId

          });


          db.query("SELECT * FROM user where   id =" + db.escape(data.userId), function (err, resultsSender, fields) {
            //console.log(results[0].created_by);

            if (resultsSender) {
              if (resultsSender.length) {

                var userImage = resultsSender[0].image;
                if (userImage == null) {
                  userImage = 'default.png';
                }

                var userImageUrl = STORAGE_URL + '/user/' + userImage;
                if (userImage == null) {
                  userImageUrl = null;
                }

                db.query("SELECT  chat_room.type,chat_room_user.id,chat_room_user.status,chat_room_user.user_id,user.device_token,user.socket_id FROM chat_room_user left join user on chat_room_user.user_id = user.id left join chat_room on chat_room_user.room_id=chat_room.id  where chat_room_user.room_id =" + room, function (err, results, fields) {


                  if (results.length) {
                    var values = [];
                    var isDelivered = false;
                    results.forEach(chatRoomUser => {
                     
                     
                      var userId = chatRoomUser.user_id;
                      if (chatRoomUser.type == 1) { //private
                        if (chatRoomUser.status != 10) { //chat group not active or deleted

                          db.query("update chat_room_user set status='10' where id =" + chatRoomUser.id, function (err, results, fields) {
                            // console.log(results)

                          })

                        }
                      }


                      var resultSocket = io.sockets.sockets.get(chatRoomUser.socket_id);

                      var status = STATUS_UNRECEIVED;// no received
                      var isUserNotify = 0;
                      if (resultSocket) {
                     

                        if (userId != socket.userId) { //  leave user that create message
                          console.log('reciever socket connected')
                          isDelivered = true;

                          socket.emit('updateMessageCurrentStatusUser', {
                            status: STATUS_RECEIVED,
                            messageId: id,
                            localMessageId: data.localMessageId,
                            room: room,
                            userId: userId,
                            receiver_id: userId
                          });


                        }
                        

                        
                        // console.log(chatRoomUser.user_id);
                        status = STATUS_RECEIVED;//  received
                        isUserNotify = 1;
                      } else { /// if user not received (not conntected with socket) then send manually push notification to user

                        console.log('reciever socket not  connected and send push notification')

                        //   console.log(userId,chatRoomUser.device_token)
                        var registrationToken = chatRoomUser.device_token;
                        if (registrationToken) {
                          //var data1 = {userId:"145", localMessageId: "vCa1RR5ZZWv8CbE9QIGe2mqH0", messageType: "2", message: "hi", room: "11", created_by: "145", created_at: "1662277966"};
                          //var data1 = {userId:"145", localMessageId: "vCa1RR5ZZWv8CbE9QIGe2mqH0", messageType: "2", message: {"image":"https://image-selling.s3.amazonaws.com/chat/16622779884067_20220904_075308_0d93db2543.png","video":""}, room: "11", created_by: "145", created_at: "1662277966"};
                          if (data.messageType != '100' && data.messageType != '200') {
                            var bodyResponse = getMessageBody(data);
                            var body = bodyResponse['messageString'];
                            var dataPush = {
                              "title": data.username,
                              "body": body,
                              "notification_type": '100',
                              "room": room.toString(),
                              "userImageUrl": userImageUrl,
                              "userId": data.userId.toString(),
                              "receiver_id": userId.toString()

                            }
                            if (bodyResponse.image) {
                              dataPush['image'] = bodyResponse.image;
                            }

                            sendPushNotification(registrationToken, dataPush);
                          }

                        }
                      }
                      isUserNotify = 1; // received on login and message seen tyme to to notify sender user

                      values.push([id, userId, status, isUserNotify]);
                    })
                    var sql = "INSERT INTO chat_message_user (chat_message_id, user_id, status,is_user_notify) VALUES ?";
                    db.query(sql, [values], function (err, result) {
                      if (err) throw err;
                      //console.log("Number of records inserted: " + result.affectedRows);
                    });
                    /// deliver statrus

                    

                    //////

                  }
                })
              }
            }

          })
        })
      } catch (err) {
        console.log(err);
        console.log('user disconnected errrRRR');

      }

    });



    /// read message status

    socket.on('readMessage', (data) => {
      console.log('read message')
      console.log(data)
     

      db.query("update chat_message_user set status='" + STATUS_READ + "' where chat_message_id = " + db.escape(data.id) + " and user_id = " + db.escape(socket.userId), function (err, results, fields) {
       

        //#region update as message current status seen

        db.query("SELECT * FROM chat_message where  id =" + db.escape(data.id), function (err, results, fields) {

          if (results) {
            if (results.length) {


              var createdBy = results[0].created_by;
              var createdAt = results[0].created_at;
              var localMessageId = results[0].local_message_id;
              var room = results[0].room_id;


              db.query("SELECT id,username,socket_id FROM user where id =" + db.escape(createdBy), function (err, resultsUserSocket, fields) {
                if (err) throw err;
                if (resultsUserSocket) {

                  var createdBySocketId = resultsUserSocket[0].socket_id;

                 
                  var resultSocket = io.sockets.sockets.get(createdBySocketId);

                  var isUserNotify = COMMON_NO;
                  if (resultSocket) {
                    console.log('seeen message sento to');

                    io.to(createdBySocketId).emit('updateMessageCurrentStatusUser', {
                      status: STATUS_READ,
                      messageId: data.id,
                      localMessageId: data.localMessageId,
                      room: room,
                      userId: socket.userId
                    });

                   
                    isUserNotify = COMMON_YES;
                  }

                  db.query("update chat_message_user set is_user_notify='" + isUserNotify + "' where chat_message_id = " + db.escape(data.id) + " and user_id = " + db.escape(socket.userId), function (err, results, fields) {

                   

                  })


                }
              })

            }

          }

        })
        //#endregion 


      })




    });


    /// delete message 

    socket.on('deleteMessage', async (data) => {
      console.log('delete message')
      username = socket.username;
      var room = data.room;
      var messageId = data.id;
      var deleteScope = data.deleteScope;
      var query = "SELECT chat_message_user.id,chat_message_user.status,chat_message_user.user_id, chat_message_user.chat_message_id,user.socket_id from chat_message_user left join user on chat_message_user.user_id = user.id where chat_message_id = " + db.escape(messageId);

      if (deleteScope == 1) {// for me=1, for everyone =2
        query = query + " and chat_message_user.user_id = " + db.escape(socket.userId);
      }
      var results = await getDbData(query);
      if (results.length) {
        results.forEach(item => {
          var resultSocket = io.sockets.sockets.get(item.socket_id);
          var isUserNotify = 0;
          if (resultSocket) {
            isUserNotify = 1;
          }
          var sql = "update chat_message_user set status='" + STATUS_DELETED + "',is_user_notify='" + isUserNotify + "' where id = " + db.escape(item.id);
          getDbData(sql);
        })
        var sendData = {
          id: messageId,
          username: username,
          room: room,
          deleteScope: deleteScope
        }
        //#region send notificaton
        if (deleteScope == 2) {// for me=1, for everyone =2
          socket.to(room).emit('deleteMessage', sendData);

        } else {
          // socket.emit('deleteMessage', sendData);

        }
      }
    });
    socket.on('deleteMessage_old', (data) => {
      console.log('delete message')
      // console.log(data)


      username = socket.username;



      var room = data.room;
      var messageId = data.id;
      var deleteScope = data.deleteScope;

      var sql = "update chat_message_user set status='" + STATUS_DELETED + "' where chat_message_id = " + db.escape(messageId);
      if (deleteScope == 1) {// for me=1, for everyone =2
        sql = sql + " and user_id = " + db.escape(socket.userId);
      }
      //console.log(sql);
      db.query(sql, function (err, results, fields) {
        console.log(results)

        var sendData = {
          id: messageId,
          username: username,
          room: room,
          deleteScope: deleteScope
        }

        //#region send notificaton
        if (deleteScope == 2) {// for me=1, for everyone =2
          socket.to(room).emit('deleteMessage', sendData);

        } else {
          // socket.emit('deleteMessage', sendData);

        }

        //#endregion 


      })




    });

    socket.on('typing', (data) => {
      console.log('typing')
      socket.to(data.room).emit('typing', {
        username: socket.username,
        room: data.room
      });
      
    });


    /** CALL CREATE */
    socket.on('callCreate', async (data) => {
      //console.log('call create')
      var userId = data.userId;
      var recieverId = data.recieverId;
      var callType = data.callType;

      var localCallId = data.localCallId;
      //var channelName =  data.channelName;
      var channelName;

      var tokenChannelRes = await createToken();

      var channelName = tokenChannelRes.channelName;
      var tokenA = tokenChannelRes.token;

      var uuid = uuidv4()


      var currentTime = Math.round((new Date()).getTime() / 1000);
      //console.log(sockets);


      // console.log(resultSocket[0]);

      var sql = "INSERT INTO call_detail (uuid,local_call_id,call_type, caller_id, receiver_id, start_time, channel_name) VALUES (" + db.escape(uuid) + "," + db.escape(localCallId) + "," + db.escape(callType) + "," + db.escape(userId) + "," + db.escape(recieverId) + "," + db.escape(currentTime) + "," + db.escape(channelName) + " )";
      db.query(sql, function (err, results) {
        if (err) throw err;
        var id = results.insertId;
        db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, results, fields) {
          if (results) {
            if (results.length) {
              var userImage = results[0].image;

              var userImageUrl = STORAGE_URL + '/user/' + userImage;
              if (userImage == null) {
                userImageUrl = null;
              }

              db.query("SELECT id,username,socket_id,device_token_voip_ios,device_token,device_type FROM user where id =" + db.escape(recieverId), function (err, resultsReceiver, fields) {
                if (err) throw err;
                if (resultsReceiver) {
                  var data = {
                    id: id.toString(),
                    callType: callType.toString(),
                    username: socket.username,
                    userImage: userImageUrl,
                    callerId: userId.toString(),
                    channelName: channelName,
                    token: tokenA,
                    uuid: uuid

                  }

                  io.to(resultsReceiver[0].socket_id).emit('callIncoming', data);
                  data['notification_type'] = '103';
                  if (resultsReceiver[0].device_type == 1) {//andoid
                    var registrationToken = resultsReceiver[0].device_token;
                  
                    data['title'] = socket.username;
                    data['body'] = 'New Call';
                    sendPushNotification(registrationToken, data);

                  } else { //ios
                    
                    let deviceToken = resultsReceiver[0].device_token_voip_ios

                    if (deviceToken) { /// send push voip notification
                      sendIosVoipPushNotification(deviceToken, data)
                    }
                  }

                }
              })

            }

          }

        })
        socket.emit('callCreateConfirm', {
          id: id,
          callType: callType,
          localCallId: localCallId,
          channelName: channelName,
          token: tokenA,
          uuid: uuid

        });
      })
    });
    socket.on('performActionOnCall', (data) => {
      //console.log('call create')
      var userId = data.userId;
      var uuid = data.uuid;
      var status = data.status;

      var currentTime = Math.round((new Date()).getTime() / 1000);
      db.query("SELECT * FROM call_detail where   uuid =" + db.escape(uuid), function (err, results, fields) {
        console.log(results);

        if (results) {
          if (results.length) {
            var receiverId = results[0].receiver_id;
            var callerId = results[0].caller_id;
            var startTime = results[0].start_time;
            var channelName = results[0].channel_name;
            var uuid = results[0].uuid;
            var callId = results[0].id;
            var callType = results[0].call_type;

            var sql = "update call_detail set status=" + status;
            if (status == 5) {
              var totalTime = currentTime - startTime;
              sql = sql + " , end_time = " + db.escape(currentTime) + " , total_time = " + db.escape(totalTime);

            }
            sql = sql + " where uuid = " + db.escape(uuid);

            db.query(sql, function (err, results, fields) {
              // console.log(results)
            })
            var toUserId;

            if (callerId == userId) {
              toUserId = receiverId;
            }

            if (receiverId == userId) {
              toUserId = callerId;

            }
            
            db.query("SELECT id,username,socket_id,device_type,device_token,device_token_voip_ios FROM user where id =" + db.escape(toUserId), function (err, resultsUserSocket, fields) {
              if (err) throw err;
              if (resultsUserSocket) {


                io.to(resultsUserSocket[0].socket_id).emit('callStatusUpdate', {
                  id: callId,
                  status: status,
                  channelName: channelName,
                  uuid: uuid
                });

                // send push notification if call cancelled

                if (status == 2) {

                  var data = {
                    id: callId.toString(),
                    callType: callType.toString(),
                    status: status.toString(),
                    channelName: channelName.toString(),
                    notification_type: '104',
                    uuid: uuid
                  }

                  //console.log(data);

                  if (resultsUserSocket[0].device_type == 1) {//andoid
                    var registrationToken = resultsUserSocket[0].device_token;

                    data['title'] = socket.username;
                    data['body'] = 'Call Cancel';
                    //data['notification_type'] = '104';


                    sendPushNotification(registrationToken, data);

                  } else { //ios

                    let deviceToken = resultsUserSocket[0].device_token_voip_ios

                    if (deviceToken) { /// send push voip notification
                      sendIosVoipPushNotification(deviceToken, data)
                    }
                  }
                }


              }
            })

          }
        }

      });

    })


    //#region go live call

    socket.on('goLive', async (data) => {
      var username = socket.username;
      console.log('go live room created request')
      var userId = data.userId;
      //  var liveCallId = data.liveCallId;
      var inputData = {
        userId: userId
      }

      await endLiveCallIfExist(socket, inputData);

      var currentTime = Math.round((new Date()).getTime() / 1000);

      var tokenChannelRes = await createToken();
      //console.log(tokenChannel)
      var channelName = tokenChannelRes.channelName;
      var token = tokenChannelRes.token;
      var query = "INSERT INTO user_live_history (user_id, start_time,channel_name,token) VALUES (" + db.escape(userId) + "," + db.escape(currentTime) + "," + db.escape(channelName) + "," + db.escape(token) + " )";
      var results = await getDbData(query);
      var liveCallId = results.insertId;
      socket.join(liveCallId);
      socket.emit('goLiveConfirm', {
        liveCallId: liveCallId,
        localCallId: data.localCallId,
        channelName: channelName,
        token: token

      });
      var role = STATUS_LIVE_CALL_USER_ROLE_SUPER_HOST;
      query = "INSERT INTO live_call_viewer (live_call_id,user_id,role, created_at,created_by) VALUES (" + db.escape(liveCallId) + "," + db.escape(userId) + "," + db.escape(role) + "," + db.escape(currentTime) + "," + db.escape(userId) + " )";
      await getDbData(query);
      // send push notification to follower user 
      db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, resultsSender, fields) {
        
        if (resultsSender) {
          if (resultsSender.length) {
            var userImage = resultsSender[0].image;
            var userImageUrl = STORAGE_URL + '/user/' + userImage;
            if (userImage == null) {
              userImageUrl = null;
            }
            db.query("SELECT  user.id,user.username,user.socket_id,user.device_token FROM follower left join user on follower.follower_id = user.id where follower.user_id =" + userId, function (err, results, fields) {
              //console.log(results);
              if (results.length) {
                results.forEach(followingUser => {

                  var registrationToken = followingUser.device_token;
                  if (registrationToken) {

                    var dataPush = {
                      "title": username + ' is live',
                      "body": username + ' is live now',
                      "notification_type": '101',
                      "liveCallId": liveCallId.toString(),
                      "channelName": channelName,
                      "token": token,
                      "userImageUrl": userImageUrl,
                      "userId": userId.toString()
                    }

                    sendPushNotification(registrationToken, dataPush);

                  }

                })

              }
            })
          }
        }

      })

    })

    //#region add user live battle

    socket.on('inviteUserInLiveBattle', async (data) => {
      var username = socket.username;
      console.log('Invite for live battle');
      console.log(data);
      var userId = data.userId;
      var liveCallId = data.liveCallId;
      var totalAllowedTime = data.totalAllowedTime;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      //#region check user socket connected
      var query = "SELECT * FROM user where  id =" + db.escape(userId);
      var resultsHost = await getDbData(query);
      var socketId = resultsHost[0].socket_id;

      //#endregion

      //#region check if user already on call
      var query = "SELECT  * from user_live_history where status= " + STATUS_LIVE_CALL_ONGOING + " and user_id =" + userId;
      var resultLiveAlready = await getDbData(query);
      if (resultLiveAlready.length) {
        console.log('alreadyLiveAnotherCall')
        socket.emit('alreadyLiveAnotherCall', {
          liveCallId: resultLiveAlready[0].id
        });
        return false;

      }
      var query = "SELECT  * from user_live_battle where status= " + STATUS_LIVE_CALL_BATTLE_ONGOING + " and host_user_id =" + userId;
      var resultLiveBattleAlready = await getDbData(query);
      if (resultLiveBattleAlready.length) {
        console.log('alreadyLiveAnotherCall battle')
        socket.emit('alreadyLiveAnotherCall', {
          liveCallId: resultLiveAlready[0].user_live_history_id,
          battleId: resultLiveAlready[0].id
        });
        return false;

      }
      //#endregion




      // invitation sent

      //#region check already sent and cancelled if any

      var query = "SELECT  * from user_live_battle where user_live_history_id= " + liveCallId + " and host_user_id =" + userId;
      var resultsLiveCallBettle = await getDbData(query);

      if (resultsLiveCallBettle.length) {
        if (resultsLiveCallBettle[0].status == STATUS_LIVE_CALL_BATTLE_PENDING) {

          var query = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_CANCELLED + " where id = " + db.escape(resultsLiveCallBettle[0].id);
          await getDbData(query);
          
        }
      }
      //#endregion

      var query = "SELECT * FROM user_live_history where   id =" + db.escape(liveCallId);
      var resultsLiveCall = await getDbData(query);

      if (resultsLiveCall) {
        var resultsCallDetail = resultsLiveCall[0];
        var channelName = resultsCallDetail.channel_name;
        var token = resultsCallDetail.token;
        //console.log(channelName);
        var query = "INSERT INTO user_live_battle (user_live_history_id,super_host_user_id,host_user_id, total_allowed_time,created_at,status) VALUES (" + db.escape(liveCallId) + "," + db.escape(socket.userId) + "," + db.escape(userId) + "," + db.escape(totalAllowedTime) + "," + db.escape(currentTime) + "," + STATUS_LIVE_CALL_BATTLE_PENDING + ")";
        var results = await getDbData(query);
        var battleId = results.insertId;

        // inviteUserInLiveBattle confirmation
        socket.emit('inviteUserInLiveBattleConfirm', {
          liveCallId: liveCallId,
          battleId: battleId,
          status: STATUS_LIVE_CALL_BATTLE_PENDING,
          totalAllowedTime: totalAllowedTime

        });
        // send inviation notification
        var query = "SELECT * FROM user where  id =" + db.escape(socket.userId);
        var resultsSuperHost = await getDbData(query);
        var userImage = resultsSuperHost[0].image;
        if (userImage == null) {
          userImage = 'default.png';
        }

        var userImageUrl = STORAGE_URL + '/user/' + userImage;
        if (userImage == null) {
          userImageUrl = null;
        }


        var socketId = resultsHost[0].socket_id;


        var inputData = {
          liveCallId: liveCallId,
          battleId: battleId
        }

        var battleInfo = await getBattleDetail(inputData);

        var inputPush = {
          liveCallId: liveCallId,
          battleId: battleId,
          battleInfo: battleInfo,
          userId: socket.userId,
          username: socket.username,
          channelName: channelName,
          token: token,
          userImageUrl: userImageUrl

        }

        io.to(socketId).emit('newliveBattleInvitation', inputPush);
      }
    })


    //#region upate status invitation battle

    socket.on('replyLiveBattleInvitation', async (data) => {
      var username = socket.username;
      console.log('reply of invitation for live battle')
      console.log(data)
      var userId = socket.userId;
      var battleId = data.battleId;
      var newStatus = data.status;
      var currentTime = Math.round((new Date()).getTime() / 1000);

      var query = "SELECT  * from user_live_battle where id= " + battleId;
      var resultsLiveCallBattle = await getDbData(query);
      if (resultsLiveCallBattle.length == 0) {
        console.log('no resultsLiveCall Battle ')
        return false;

      }
      var resultsLiveCallBattleDetail = resultsLiveCallBattle[0];
      //console.log(resultsLiveCallHostDetail);
      if (resultsLiveCallBattleDetail.status != STATUS_LIVE_CALL_BATTLE_PENDING) {
        console.log('reply battle : alreadyStatusUpdatedLiveBattleInvitation ')
        socket.emit('alreadyStatusUpdatedLiveBattleInvitation', {
          battleId: battleId
        });
        return false;
      }
      var liveCallId = resultsLiveCallBattleDetail.user_live_history_id;

      var query = "SELECT  * from user_live_history where id= " + liveCallId;
      var resultsLiveCall = await getDbData(query);
      var resultsLiveCallDetail = resultsLiveCall[0];
      console.log(resultsLiveCallDetail);

      if (resultsLiveCallDetail.status != STATUS_LIVE_CALL_ONGOING) {

        console.log('reply battle : call status has been closed now ')
        return false;
      }

      if (newStatus == STATUS_LIVE_CALL_BATTLE_REJECTED) { // if rejected

        var query = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_REJECTED + " where id = " + db.escape(battleId);
        var resultsLiveCallBattleUpdate = await getDbData(query);

        /// get super admin and inform the reply

        var query = "SELECT  user.id,user.username,user.socket_id from user where id= " + resultsLiveCallDetail.user_id;
        var resultsUserSuperHost = await getDbData(query);

        var socketId = resultsUserSuperHost[0].socket_id;

        
        var dataPush = {
          liveCallId: liveCallId,
          battleId: battleId,
          userId: userId,
          username: socket.username,
          status: newStatus
        }

        io.to(socketId).emit('liveBattleInvitationUpated', dataPush);
        console.log('rejected notitifcation');
        console.log(dataPush);

        return false;


      } else if (newStatus == STATUS_LIVE_CALL_BATTLE_ACCEPTED) { // if accepted
        console.log('reply battle : accepted call ')
        var query = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_ONGOING + ",start_time=" + currentTime + " where id = " + db.escape(battleId);
        await getDbData(query);


        /// get super admin and inform the reply

        var query = "SELECT  * from user where id= " + resultsLiveCallDetail.user_id;
        var resultsUserSuperHost = await getDbData(query);

        var socketId = resultsUserSuperHost[0].socket_id;
        //console.log(socketId)
        var inputData = {
          liveCallId: liveCallId
        }
        //var liveBattleHosts = await getLiveBattleUser(inputData);
        var battleInfo = await getLiveBattleInfo(inputData);


        io.to(liveCallId).emit('liveBattleInvitationUpated', {
          liveCallId: liveCallId,
          battleId: battleId,
          userId: userId,
          username: socket.username,
          status: STATUS_LIVE_CALL_BATTLE_ONGOING,
          battleInfo: battleInfo
        });

        
        socket.join(liveCallId); //user join 


        ///send in group for live bettle host
    
        io.to(liveCallId).emit('liveBattleHostUpdated', {
          liveCallId: liveCallId,
          battleId: battleId,
          battleInfo: battleInfo
        });


        var query = "SELECT  * from user where id= " + userId;
        var resultsSender = await getDbData(query);

        if (resultsSender.length) {

          var userImage = resultsSender[0].image;
          if (userImage == null) {
            userImage = 'default.png';
          }
          var userImageUrl = STORAGE_URL + '/user/' + userImage;
          if (userImage == null) {
            userImageUrl = null;
          }

          var channelName = resultsLiveCallDetail.channel_name;
          var token = resultsLiveCallDetail.token;

          db.query("SELECT  user.id,user.username,user.socket_id,user.device_token FROM follower left join user on follower.follower_id = user.id where follower.user_id =" + userId, function (err, results, fields) {
            //console.log(results);
            if (results.length) {
              results.forEach(followingUser => {

                var registrationToken = followingUser.device_token;
                if (registrationToken) {

                  var dataPush = {
                    "title": username + ' is live in battle',
                    "body": username + ' is live now in battle',
                    "notification_type": '111',
                    "liveCallId": liveCallId.toString(),
                    "channelName": channelName,
                    "token": token,
                    "userImageUrl": userImageUrl,
                    "userId": userId.toString()
                  }

                  sendPushNotification(registrationToken, dataPush);


                }

              })

            }
          })
        }

      }



    })



    socket.on('goLive_OLD', async (data) => {
      var username = socket.username;
      console.log('go live room created')
      var userId = data.userId;
      //  var liveCallId = data.liveCallId;
      var currentTime = Math.round((new Date()).getTime() / 1000);
      db.query("SELECT  * from user_live_history where status= " + STATUS_LIVE_CALL_ONGOING + " and user_id =" + userId, async function (err, resultLiveAlready, fields) {
        if (resultLiveAlready.length) { // check already live call
          console.log('already live call id ', resultLiveAlready[0].id);
          socket.emit('alreadyLiveAnotherCall', {
            liveCallId: resultLiveAlready[0].id
          });

        } else { // create live call
          var tokenChannelRes = await createToken();
          //console.log(tokenChannel)
          var channelName = tokenChannelRes.channelName;
          var token = tokenChannelRes.token;

          var sql = "INSERT INTO user_live_history (user_id, start_time,channel_name,token) VALUES (" + db.escape(userId) + "," + db.escape(currentTime) + "," + db.escape(channelName) + "," + db.escape(token) + " )";
          db.query(sql, function (err, results) {
            if (err) throw err;
            var liveCallId = results.insertId;
            console.log(liveCallId);
            socket.join(liveCallId);

            socket.emit('goLiveConfirm', {
              liveCallId: liveCallId,
              localCallId: data.localCallId,
              channelName: channelName,
              token: token

            });

            db.query("SELECT * FROM user where   id =" + db.escape(userId), function (err, resultsSender, fields) {
              //console.log(results[0].created_by);

              if (resultsSender) {
                if (resultsSender.length) {

                  var userImage = resultsSender[0].image;
                  if (userImage == null) {
                    userImage = 'default.png';
                  }
                  var userImageUrl = STORAGE_URL + '/user/' + userImage;
                  if (userImage == null) {
                    userImageUrl = null;
                  }

                  db.query("SELECT  user.id,user.username,user.socket_id,user.device_token FROM follower left join user on follower.follower_id = user.id where follower.user_id =" + userId, function (err, results, fields) {
                    //console.log(results);

                    if (results.length) {
                      results.forEach(followingUser => {

                        var registrationToken = followingUser.device_token;
                        if (registrationToken) {
                          var dataPush = {
                            "title": username + ' is live',
                            "body": username + ' is live now',
                            "notification_type": '101',
                            "liveCallId": liveCallId.toString(),
                            "channelName": channelName,
                            "token": token,
                            "userImageUrl": userImageUrl,
                            "userId": userId.toString()
                          }
                          sendPushNotification(registrationToken, dataPush);
                        }

                      })
                    }
                  })
                }
              }

            })

          })

        }
      })
    })

    // end call
    socket.on('endLiveCall', (data) => {
      console.log('end  live call')

      var userId = data.userId;
      var inputData = {
        userId: userId
      }
      endLiveCallIfExist(socket, inputData);

    })


    socket.on('leaveUserLiveCall', async (data) => {
      console.log('leave live call')
      var userId = data.userId;
      var liveCallId = data.liveCallId;

      var sql = "delete from live_call_viewer  where  live_call_id =" + db.escape(liveCallId) + " and  user_id = " + db.escape(userId);
      getDbData(sql);

      var inputData = {
        liveCallId: liveCallId
      }
      var totalUser = await getLiveCallUserCount(inputData);

      socket.to(liveCallId).emit('leaveUserLiveCall', {
        username: socket.username,
        userId: socket.userId,
        liveCallId: liveCallId,
        totalUser: totalUser
      });
      socket.leave(liveCallId);
      var sql = "delete from live_tv_viewer  where  live_call_id =" + db.escape(liveCallId) + " and  user_id = " + db.escape(userId);
      db.query(sql, function (err, results, fields) {
        //console.log('remove from live call viewer list')
      })

    })


    socket.on('addUserLiveCall', async (data) => {

      var userId = data.userId;
      var liveCallId = data.liveCallId;
      var currentTime = Math.round((new Date()).getTime() / 1000);


      // START check use is ban or not
      var query = "SELECT  * from live_call_viewer where  live_call_id= " + liveCallId + " and user_id= " + userId;
      var resultsLiveCallUserAction = await getDbData(query);
      if (resultsLiveCallUserAction.length > 0) {
        var userActionRecord = resultsLiveCallUserAction[0];
        if (userActionRecord['is_ban']) {
          var doBan = false;
          if (userActionRecord['ban_type'] == STATUS_LIVE_CALL_BAN_TYPE_WHOLE_CALL) {
            console.log('you are not allowed to enter this call')
            doBan = true;
          } else if (userActionRecord['ban_type'] == STATUS_LIVE_CALL_BAN_TYPE_TIME_PERIOD) {

            if (userActionRecord['expel_expiry_time'] > currentTime) {
              console.log('you are not allowed to enter this call till ' + userActionRecord['expel_expiry_time'])
              doBan = true;
            }
          }
          if (doBan) {
            socket.emit('liveCallNotAllowed', {
              liveCallId: liveCallId,
              banType: userActionRecord['ban_type'],
              expelExpiryTime: userActionRecord['expel_expiry_time'],
            });
            return false;
          }

        }
        var query = "delete from live_call_viewer where  id= " + userActionRecord['id'];
        await getDbData(query);

      }

      // END check use is ban or not

      console.log('user added in live call')

      socket.join(liveCallId);

      var role = STATUS_LIVE_CALL_USER_ROLE_USER;
      query = "INSERT INTO live_call_viewer (live_call_id,user_id,role, created_at,created_by) VALUES (" + db.escape(liveCallId) + "," + db.escape(userId) + "," + db.escape(role) + "," + db.escape(currentTime) + "," + db.escape(userId) + " )";
      await getDbData(query);
      var inputData = {
        liveCallId: liveCallId
      }
      var totalUser = await getLiveCallUserCount(inputData);
     
      io.to(liveCallId).emit('addUserLiveCall', {
        username: socket.username,
        userId: socket.userId,
        liveCallId: liveCallId,
        totalUser: totalUser
      });


      ///send in group for live bettle host
      var inputData = {
        liveCallId: liveCallId
      }
    
      var battleInfo = await getLiveBattleInfo(inputData);
      if (battleInfo.liveBattleHosts.length > 0) {
        socket.emit('liveBattleHostUpdated', {
          liveCallId: liveCallId,
          battleInfo: battleInfo

        });
      }

    })

    socket.on('sendMessageLiveCall', async (data) => {

      data.username = socket.username;

      data.created_at = Math.round((new Date()).getTime() / 1000);
      data.created_by = data.userId;
      var liveCallId = data.liveCallId;

      socket.to(liveCallId).emit('sendMessageLiveCall', data);

      // update total comments
      var query = "SELECT  * from user_live_history where id= " + liveCallId;
      var resultsLiveCall = await getDbData(query);
      var resultsLiveCallDetail = resultsLiveCall[0];
      var totalComments = resultsLiveCallDetail.total_comment;
      var totalNewComments =  totalComments + 1;
      var query = "update user_live_history set total_comment=" + totalNewComments + " where " + liveCallId;
      await getDbData(query);
      // end update total comments
      
      socket.emit('updateMessageStatusLiveCall', {
        current_status: 1,
        localMessageId: data.localMessageId,
        liveCallId: liveCallId,
        created_at: data.created_at

      });



    });


    socket.on('endLiveBattle', async (data) => {
      console.log('end live battle')
      var battleId = data.battleId;
      var currentTime = Math.round((new Date()).getTime() / 1000);
      var query = "SELECT  * from user_live_battle  where id= " + battleId;
      var resultsBattles = await getDbData(query);
      if (resultsBattles.length == 0) {
        console.log('No battle found')
        return false;

      }

      var resultLiveBattleDetail = resultsBattles[0];

      var battleId = resultLiveBattleDetail.id;
      var startTime = resultLiveBattleDetail.start_time;
      var totalTime = currentTime - startTime;
      var liveCallId = resultLiveBattleDetail.user_live_history_id;

      var sql = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_COMPLETED + ", end_time = " + db.escape(currentTime) + " , total_time = " + db.escape(totalTime) + " where id = " + db.escape(battleId);
      getDbData(sql);


      io.to(liveCallId).emit('endLiveBattle', {
        username: socket.username,
        userId: socket.userId,
        liveCallId: liveCallId,
        battleId: battleId
      });
    })

    socket.on('sendGiftLiveCall', async (data) => {
      console.log('send gift to live user call')
      var userId = socket.userId;
      var receiverId = data.userId;
      var liveCallId = data.liveCallId;
      var battleId = data.battleId;
      var giftId = data.giftId;

      var currentTime = Math.round((new Date()).getTime() / 1000);

      var query = "SELECT  * from gift  where id= " + giftId;
      var resultsGifts = await getDbData(query);
      if (resultsGifts.length == 0) {
        console.log('No gift record available')
        return false;
        
      }

      var resultsGift = resultsGifts[0];
      var giftCoin = resultsGift.coin;
      var isPaidGift = resultsGift.is_paid;

      var query = "SELECT  user.id,user.username,user.socket_id,available_coin, user.image from user where id= " + userId;
      var resultUserSender = await getDbData(query);
      var userSender = resultUserSender[0];
      var availableCoin = userSender.available_coin;



      if (giftCoin > availableCoin) {
        socket.emit('notEnoughBalanceLiveCallGift', {
          availableCoin: availableCoin,
          gitCoin: giftCoin
        });
      }

      var query = "SELECT * FROM setting";
      var resultSettings = await getDbData(query);
      var resultSetting = resultSettings[0];
      var adminAvailableCoin = resultSetting.available_coin;
      var commissionOnGiftPercent = resultSetting.commission_on_gift;


      var adminCommissionCoin = 0;
      if (commissionOnGiftPercent > 0) {
        adminCommissionCoin = giftCoin / 100 * commissionOnGiftPercent;

      }
      var userGetCoin = giftCoin - adminCommissionCoin;
      var query;
      if (battleId > 0) {
        query = "INSERT INTO gift_history (sender_id,reciever_id,gift_id,coin,coin_actual, send_on_type, live_call_id,battle_id,created_at) VALUES (" + db.escape(userId) + "," + db.escape(receiverId) + "," + db.escape(giftId) + "," + db.escape(userGetCoin) + "," + db.escape(giftCoin) + "," + db.escape(1) + "," + db.escape(liveCallId) + "," + db.escape(battleId) + "," + db.escape(currentTime) + " )";
      } else {
        query = "INSERT INTO gift_history (sender_id,reciever_id,gift_id,coin,coin_actual,send_on_type, live_call_id,created_at) VALUES (" + db.escape(userId) + "," + db.escape(receiverId) + "," + db.escape(giftId) + "," + db.escape(userGetCoin) + "," + db.escape(giftCoin) + "," + db.escape(1) + "," + db.escape(liveCallId) + "," + db.escape(currentTime) + " )";
      }

      var resultInsert = await getDbData(query);
      var gitHistoryId = resultInsert.insertId;


      if (isPaidGift == 1) {
        if (gitHistoryId) {

          var availableCoinNew = availableCoin - giftCoin;
          var query = "update user set available_coin=" + availableCoinNew + " where id = " + userId;
          var userUpdated = await getDbData(query);
          if (userUpdated) {

            /// enty for debit coin from user account
            var type = 2; //coin
            var transaction_type = 2; //debit
            var payment_type = 6; //gift
            var payment_mode = 3; //wallet

            var query = "INSERT INTO payment (type, user_id, transaction_type, payment_type, payment_mode, coin, gift_history_id,created_at) VALUES (" + db.escape(type) + "," + db.escape(userId) + "," + db.escape(transaction_type) + "," + db.escape(payment_type) + "," + db.escape(payment_mode) + "," + db.escape(giftCoin) + "," + db.escape(gitHistoryId) + "," + db.escape(currentTime) + " )";

            var resultInsert = await getDbData(query);


            /// enty for credit gift coin from user reciever


            var query = "SELECT  user.id,user.username,user.socket_id,available_coin from user where id= " + receiverId;
            var resultUserReciever = await getDbData(query);
            var userReciever = resultUserReciever[0];
            var availableCoin = userReciever.available_coin;

            var availableCoinNew = availableCoin + userGetCoin;
            var query = "update user set available_coin=" + availableCoinNew + " where id = " + receiverId;
            var userUpdated = await getDbData(query);

            var type = 2; //coin
            var transaction_type = 1; //credit
            var payment_type = 6; //gift
            var payment_mode = 3; //wallet

            var query = "INSERT INTO payment (type, user_id, transaction_type, payment_type, payment_mode, coin, gift_history_id,created_at) VALUES (" + db.escape(type) + "," + db.escape(receiverId) + "," + db.escape(transaction_type) + "," + db.escape(payment_type) + "," + db.escape(payment_mode) + "," + db.escape(userGetCoin) + "," + db.escape(gitHistoryId) + "," + db.escape(currentTime) + " )";
            var resultInsert = await getDbData(query);



            /// entry for admin commission 

            if (adminCommissionCoin > 0) {


              var adminAvailableCoinNew = adminAvailableCoin + adminCommissionCoin;
              var query = "update setting set available_coin=" + adminAvailableCoinNew + " where id = 1";
              var settingUpdated = await getDbData(query);

              var adminId = 1;
              var type = 2; //coin
              var transaction_type = 1; //credit
              var payment_type = 13; //gift commistion for admin
              var payment_mode = 3; //wallet

              var query = "INSERT INTO payment (type, user_id, transaction_type, payment_type, payment_mode, coin, gift_history_id,created_at) VALUES (" + db.escape(type) + "," + db.escape(adminId) + "," + db.escape(transaction_type) + "," + db.escape(payment_type) + "," + db.escape(payment_mode) + "," + db.escape(adminCommissionCoin) + "," + db.escape(gitHistoryId) + "," + db.escape(currentTime) + " )";
              var resultInsert = await getDbData(query);

            }
          }
        }
      }



      var inputData = {
        liveCallId: liveCallId,
        battleId: battleId
      }
      //var liveBattleHosts = await getLiveBattleUser(inputData)
      var battleInfo = await getLiveBattleInfo(inputData);


      var giftImageUrl = STORAGE_URL + '/gift/' + resultsGift.image;
      var senderImageUrl = STORAGE_URL + '/user/' + userSender.image;

      if (giftImageUrl == null) {
        senderImageUrl = null;
      }

      data.giftUrl = giftImageUrl;
      data.name = resultsGift.name;
      data.coin = resultsGift.coin;
      data.battleInfo = battleInfo;
      data.senderId = userId;
      data.senderName = userSender.username;
      data.senderImageUrl = senderImageUrl;


      io.to(liveCallId).emit('newGiftReceivedliveCall', data);

    })

    socket.on('liveChatUpdateUserAction', async (data) => {
      console.log('udate user role/action in call')
      var userId = socket.userId;
      var actionUserId = data.actionUserId;
      var liveCallId = data.liveCallId;
      var actionType = data.actionType; //(ban=1,unban=2,roleUpdate=3)
      var totalExpelTime = data.totalExpelTime;
      var role = data.role;

      var currentTime = Math.round((new Date()).getTime() / 1000);

      if (actionType == STATUS_LIVE_CALL_ACTION_TYPE_BAN) {
        var query = "SELECT  * from live_call_viewer where  live_call_id= " + liveCallId + " and user_id= " + actionUserId;
        var resultsLiveCallUserAction = await getDbData(query);
        if (resultsLiveCallUserAction.length > 0) {
          
          var query;
          var isBane = 1;
          var expelExpiryTime = 0;
          if (totalExpelTime > 0) { // for time specified
            var baneType = 2;
            expelExpiryTime = totalExpelTime + currentTime;
          } else {
            var baneType = 1;
            totalExpelTime = 0;
            expelExpiryTime = 0;
          }
          query = "UPDATE live_call_viewer set is_ban=" + db.escape(isBane) + ",ban_type=" + db.escape(baneType) + ",total_expel_time=" + db.escape(totalExpelTime) + ",expel_expiry_time=" + db.escape(expelExpiryTime) + ", updated_at=" + db.escape(currentTime) + ",updated_by=" + db.escape(userId) + " where id= " + resultsLiveCallUserAction[0]['id'];
          var resultAction = await getDbData(query);
          if (resultAction) {
            socket.emit('liveChatUpdateUserActionConfirm', data);
            // remove from room
            var query = "SELECT id,socket_id,username FROM user where   id =" + db.escape(actionUserId);
            var resultsRemoved = await getDbData(query);
            if (resultsRemoved) {
              if (resultsRemoved.length) {
                // io.to(room).emit('removeUserFromRoom', sendData);
                var socketId = resultsRemoved[0].socket_id;
                var resultSocket = io.sockets.sockets.get(socketId);
                var inputData = {
                  liveCallId: liveCallId
                }
                var totalUser = await getLiveCallUserCount(inputData);

                io.to(liveCallId).emit('leaveUserLiveCall', {
                  username: resultsRemoved[0].username,
                  userId: actionUserId,
                  liveCallId: liveCallId,
                  totalUser: totalUser
                });
                if (resultSocket) { // if user online 
                  resultSocket.leave(liveCallId);
                }
              }

            }
            return false

          }
        }

      } else if (actionType == STATUS_LIVE_CALL_ACTION_TYPE_UNBAN) {
        var query = "SELECT  * from live_call_viewer where  live_call_id= " + liveCallId + " and user_id= " + actionUserId;
        var resultsLiveCallUserAction = await getDbData(query);
        if (resultsLiveCallUserAction.length) {
          var query = "delete  from live_call_viewer where  id= " + resultsLiveCallUserAction[0]['id'];
          var resultAction = await getDbData(query);
          if (resultAction) {
            socket.emit('liveChatUpdateUserActionConfirm', data);
            return false

          }
        }

      } else if (actionType == STATUS_LIVE_CALL_ACTION_TYPE_ROLE_UPDATE) {
        var query = "SELECT  * from live_call_viewer where  live_call_id= " + liveCallId + " and user_id= " + actionUserId;
        var resultsLiveCallUserAction = await getDbData(query);
        if (resultsLiveCallUserAction.length) {

          var query = "UPDATE live_call_viewer set role=" + db.escape(role) + ", user_id = " + db.escape(actionUserId) + ",is_ban=0,ban_type=0,total_expel_time=0,expel_expiry_time=0, updated_at=" + db.escape(currentTime) + ",updated_by=" + db.escape(userId) + " where id= " + resultsLiveCallUserAction[0]['id'];
          var resultAction = await getDbData(query);
          if (resultAction) {
            socket.emit('liveChatUpdateUserActionConfirm', data);
            socket.to(liveCallId).emit('liveChatUpdateUserActionUpdate', data);
            return false

          }
        }
      }
    })


    //#endregion   live call

    //#region   live tv 
    socket.on('addUserLiveTv', (data) => {
      console.log('user added in live tv')
      var userId = data.userId;
      var liveTvId = data.liveTvId;

      socket.join(liveTvId);
      socket.to(liveTvId).emit('addUserLiveTv', {
        username: socket.username,
        userId: socket.userId,
        liveTvId: liveTvId
      });


      const myArrayLiveTv = liveTvId.split("_");
      let skliveTvId = myArrayLiveTv[1];


      var currentTime = Math.round((new Date()).getTime() / 1000);

      db.query("SELECT count(id) as total FROM live_tv_viewer where   live_tv_id =" + db.escape(skliveTvId) + " and user_id =" + db.escape(userId), function (err, results, fields) {

        if (results[0].total == 0) {

          var sql = "INSERT INTO live_tv_viewer (user_id, live_tv_id,created_at) VALUES (" + db.escape(userId) + "," + db.escape(skliveTvId) + "," + db.escape(currentTime) + " )";
          db.query(sql, function (err, results) {
            if (err) throw err;

          })

        }
      })

    })


    socket.on('leaveUserLiveTv', (data) => {
      console.log('leave live Tv')
      var userId = data.userId;
      var liveTvId = data.liveTvId;

      socket.to(liveTvId).emit('leaveUserLiveTv', {
        username: socket.username,
        userId: socket.userId,
        liveTvId: liveTvId
      });
      socket.leave(liveTvId);
    })


    socket.on('sendMessageLiveTv', (data) => {

      data.username = socket.username;

      data.created_at = Math.round((new Date()).getTime() / 1000);
      data.created_by = data.userId;
      var liveTvId = data.liveTvId;

      socket.to(liveTvId).emit('sendMessageLiveTv', data);


      socket.emit('updateMessageStatusLiveTv', {
        current_status: 1,
        localMessageId: data.localMessageId,
        liveTvId: liveTvId,
        created_at: data.created_at

      });

    });

    //#endregion   live tv

    socket.on('sendNotificationTest', (data) => {
      console.log('typing')

      // This registration token comes from the client FCM SDKs.
      const registrationToken = 'eUzWru_LqrDO28TPywKTj5:APA91bFKpKN6qy6q3IXgO8Mkxy7bf22CrUO7mqyddT_MwKXLw4YHkaGrxkU03e1suVMwSGlV4cn9ek8ziM7Y1Rkv-lz3x5ZymzbLg6FsbgAuSRvPOJGsnh9TEKWSVTQ-ZY8pOVqSHAl4';
      //const registrationToken = 'fwKUWuzH3UehqKs-TdXET-:APA91bHr6n_xhPqEcNrQKaz_g6R2FI495jA7d-5kVDsIQBMVbUNATefeL6Sj8fmW48WgVApyzuvWFKS32BThsXujfDEXRGARcnxfoBgNQX0zRA7tKIhKo4dRSrepcOkQXWu2mwVm444Y';


      const message = {
        data: {
          score: '8520',
          time: '2:43'
        },
        token: registrationToken
      };

      // Send a message to the device corresponding to the provided
      // registration token.
      admin.messaging().send(message)
        .then((response) => {
          // Response is a message ID string.
          console.log('Successfully sent message:', response);
        })
        .catch((error) => {
          console.log('Error sending message:', error);
        });

    });

  });


  function getMessageBody(data) {
    
    var response = {};

    var messageString = '';
    response['messageString'] = '';
    response['image'] = '';

    
    var messageData = doDecrypt(data.message);
    console.log(messageData);
    

    var messageType = messageData.messageType;

    if (messageType == 1) {

      // response['messageString'] = messageData.text;
      response['messageString'] = doDecrypt(messageData.text);



    } else if (messageType == 2) {
      response['messageString'] = 'Sent an image';
      // response['image'] =data.message.image;

    } else if (messageType == 3) {
      response['messageString'] = 'Sent a video';
    } else if (messageType == 4) {
      response['messageString'] = 'Sent and audio';
    } else if (messageType == 5) {
      response['messageString'] = 'Sent a gif file';
    } else if (messageType == 6) {
      response['messageString'] = 'Sent a stiker';
    } else if (messageType == 7) {
      response['messageString'] = 'Shared the contact';
    } else if (messageType == 8) {
      response['messageString'] = 'Shared the location';
    }

    else if (messageType == 10) {
      response['messageString'] = 'forword the message';
    } else if (messageType == 11) {
      response['messageString'] = 'Sent a  post';
    } else if (messageType == 12) {
      response['messageString'] = 'Sent a story';
    } else if (messageType == 13) {
      response['messageString'] = 'Sent a drawing';
    } else if (messageType == 14) {
      response['messageString'] = 'Sent a profile';
    } else if (messageType == 15) {
      response['messageString'] = 'Sent a group';
    } else if (messageType == 16) {
      response['messageString'] = 'Sent a file';
    } else if (messageType == 17) {
      response['messageString'] = 'Replied on your story';
    } else if (messageType == 18) {
      response['messageString'] = 'Reacted on your story';
    }
    else if (messageType == 20) {
      response['messageString'] = 'Reacted on your message';
    } else if (messageType == 100) {
      response['messageString'] = 'Group action';
    } else if (messageType == 200) {
      response['messageString'] = 'Send a gift';
    } else {
      response['messageString'] = 'Sent a message';
    }
    return response;

  }


  function sendPushNotification(deviceToken, dataPush) {

    //var deviceToken='ejLGl1mcTYC8-xY33bO7WY:APA91bHtEbsjXv_4mPpDx-k68kN0eLI5l-qlYMqYoqFN6n44jcnVSesWgPP7VhLrgQozhPVT6yuogzpkbYnk1Mu-XMv4IfinN1pK-5yAo0FmKRWYO4uv4Eo';

    if (dataPush.userImageUrl == null) {
      dataPush.userImageUrl = '';
    }

    if (dataPush.userImage == null) {
      dataPush.userImage = '';
    }

    dataPush["sound"] = "default";
    dataPush["content_available"] = "true";
    dataPush["priority"] = "high";


    console.log(dataPush.body);

    var body = dataPush.body;
    body = body.toString();

    if (body.length > 100) {
      body = body.substring(0, 100);
    }
    dataPush["body"] = body
    var message = {
      "notification": {
        "title": dataPush.title,
        "body": body

      },
      "data": dataPush,
      token: deviceToken

    };
    console.log(message);

    // Send a message to the device corresponding to the provided
    // registration token.
    admin.messaging().send(message)
      .then((response) => {
        // Response is a message ID string.
        console.log('Successfully sent message:', response);
      })
      .catch((error) => {
        console.log('Error sending message:', error);
      });
  }

  function sendIosVoipPushNotification(deviceToken, data) {
    // console.log(data)

    //var deviceToken = "79f2dd9dc247c30e1ab488bf352eb7c88eee0d519d1347fa99453e8ed2d5dcfd" // voip token
    var options = {
      token: {
        key: config.voipNotification.key,
        keyId: config.voipNotification.keyId,
        teamId: config.voipNotification.teamId
      },
      production: config.voipNotification.production
    };

    var apnProvider = new apn.Provider(options);
    var note = new apn.Notification();

    note.expiry = Math.floor(Date.now() / 1000) + 60; // Expires 60 sec. from now. //3600 = 1 hrs
    note.badge = 1;
    note.sound = "ping.aiff";
    note.alert = "New call";

    //note.payload = {'messageFrom': 'John Appleseed'};
    note.payload = data;

    note.topic = config.voipNotification.bundleId + ".voip";

    apnProvider.send(note, deviceToken).then((err, result) => {
      console.log(result)
      if (err) {
        console.log(JSON.stringify(err));
        // res.end(JSON.stringify(err));
      } else {
        console.log(JSON.stringify(result))
        // res.end(JSON.stringify(result));
      }

    });

  }

  async function createToken() { //agora token

    let query = "SELECT * FROM setting";

    var results = await getDbData(query);


    var channelName = uuidv4()
    // Rtc Examples

    const appID = results[0].agora_api_key;
    const appCertificate = results[0].agora_app_certificate

    const uid = 0;
    const account = "0";
    const role = RtcRole.PUBLISHER;
    const expirationTimeInSeconds = 3600

    const currentTimestamp = Math.floor(Date.now() / 1000)

    const privilegeExpiredTs = currentTimestamp + expirationTimeInSeconds

    // IMPORTANT! Build token with either the uid or with the user account. Comment out the option you do not want to use below.

    // Build token with uid
    const tokenA = RtcTokenBuilder.buildTokenWithUid(appID, appCertificate, channelName, uid, role);

    return { "channelName": channelName, "token": tokenA };

  }
  function doEncrypt(data) {
    // Initializing the original data
    if (typeof data == 'object') {
      data = JSON.stringify(data);
    }
    var encryptionKey = config.encryptionKey;

    var encrypted = crypto.AES.encrypt(data, encryptionKey).toString();
    return encrypted;
  }

  function doDecrypt(data) {
    var encryptionKey = config.encryptionKey;
    var decrypted = crypto.AES.decrypt(data, encryptionKey).toString(crypto.enc.Utf8);

    if (isJsonString(decrypted)) {
      decrypted = JSON.parse(decrypted);
    }


    return decrypted;

  }
  function isJsonString(str) {
    try {
      JSON.parse(str);
    } catch (e) {
      return false;
    }
    return true;
  }

  function getDbData(query) {
    return new Promise((resolve, reject) => {

      db.query(query, function (err, results, fields) {
        if (err) {
          return reject(err);
        }

        return resolve(results);
      });
    });

  }


  async function endLiveCallIfExist(socket, data) {
    userId = data.userId;
    var currentTime = Math.round((new Date()).getTime() / 1000);

    var query = "SELECT  * from user_live_history where status= " + STATUS_LIVE_CALL_ONGOING + " and user_id =" + userId;
    var resultLiveAlready = await getDbData(query);
    if (resultLiveAlready.length) {

      var liveCallId = resultLiveAlready[0].id;
      // complete the main call status
      var startTime = resultLiveAlready[0].start_time;
      var totalTime = currentTime - startTime;
      var sql = "update user_live_history set status=" + STATUS_LIVE_CALL_COMPLETED + ", end_time = " + db.escape(currentTime) + " , total_time = " + db.escape(totalTime) + " where id = " + db.escape(liveCallId);
      getDbData(sql);

      var query = "SELECT  * from user_live_battle where status= " + STATUS_LIVE_CALL_BATTLE_ONGOING + " and super_host_user_id =" + userId;
      var resultLiveBattleAlready = await getDbData(query);
      if (resultLiveBattleAlready.length) {

        resultLiveBattleAlready.forEach(resultLiveBattleDetail => {
          // complete the old open battle
          var battleId = resultLiveBattleDetail.id;
          var startTime = resultLiveBattleDetail.start_time;
          var totalTime = currentTime - startTime;
          var sql = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_COMPLETED + ", end_time = " + db.escape(currentTime) + " , total_time = " + db.escape(totalTime) + " where id = " + db.escape(battleId);
          getDbData(sql);
        })

      }
      socket.to(liveCallId).emit('endLiveCall', {
        liveCallId: liveCallId,
        userId: socket.userId,
        username: socket.username
      });
      io.in(liveCallId).socketsLeave(liveCallId);

    }

    var query = "SELECT  * from user_live_battle where status= " + STATUS_LIVE_CALL_BATTLE_ONGOING + " and host_user_id =" + userId;
    var resultLiveBattleAlready = await getDbData(query);
    if (resultLiveBattleAlready.length) {

      resultLiveBattleAlready.forEach(async (resultLiveBattleDetail) => {
        // complete the old open battle
        var battleId = resultLiveBattleDetail.id;
        var liveCallId = resultLiveBattleDetail.user_live_history_id;
        var startTime = resultLiveBattleDetail.start_time;
        var totalTime = currentTime - startTime;
        var sql = "update user_live_battle set status=" + STATUS_LIVE_CALL_BATTLE_COMPLETED + ", end_time = " + db.escape(currentTime) + " , total_time = " + db.escape(totalTime) + " where id = " + db.escape(battleId);
        getDbData(sql);

        ///send in group for live bettle host
        var inputData = {
          liveCallId: liveCallId,
          battleId: battleId
        }

        var battleInfo = await getLiveBattleInfo(inputData);

        io.to(liveCallId).emit('liveBattleHostUpdated', {
          liveCallId: liveCallId,
          battleId: battleId,
          battleInfo: battleInfo

        });

      })
    }
  }

  async function getLiveBattleInfo(data) {
    var liveCallId = data.liveCallId;
    var battleInfo = {}
    var battleDetail = {};
    var hostArray = []
    var query = "SELECT  user_live_battle.id , user_live_battle.super_host_user_id , user_live_battle.host_user_id,start_time,end_time,total_time,total_allowed_time from user_live_battle  where user_live_battle.status= " + STATUS_LIVE_CALL_BATTLE_ONGOING + " and user_live_battle.user_live_history_id =" + liveCallId;
    var resultLiveCallBattles = await getDbData(query);
    if (resultLiveCallBattles.length) {
      var resultLiveCallBattle = resultLiveCallBattles[0];
      battleDetail = resultLiveCallBattle;
      let currentTime = Math.round((new Date()).getTime() / 1000);
      var totalOngoingTime = currentTime - battleDetail.start_time;
      battleDetail.totalOngoingTime = totalOngoingTime;
      battleDetail.timeToEnd = battleDetail.total_allowed_time - totalOngoingTime;
      //super host
      var query = "SELECT user.id,user.username, user.image from user where user.id =" + resultLiveCallBattle.super_host_user_id;
      var userSuperHosts = await getDbData(query);
      if (userSuperHosts.length) {
        var userSuperHost = userSuperHosts[0];

        var query = "SELECT sum(coin) as total_coin, count(id) as total_gift from gift_history where gift_history.reciever_id =" + resultLiveCallBattle.super_host_user_id + " and gift_history.battle_id =" + resultLiveCallBattle.id;
        var userGiftResults = await getDbData(query);

        var userGiftResult = userGiftResults[0];

        if (userSuperHost.image == null) {
          userSuperHost.image = 'default.png';
        }
        var userImageUrl = STORAGE_URL + '/user/' + userSuperHost.image;
        if (userSuperHost.image == null) {
          userImageUrl = null;
        }
        var totalCoin = Math.floor(userGiftResult.total_coin);

        var hostInnerArray = {
          userId: userSuperHost.id,
          username: userSuperHost.username,
          userImageUrl: userImageUrl,
          liveCallId: liveCallId,
          battleId: resultLiveCallBattle.id,
          isSuperHost: 1,
          totalCoin: totalCoin,
          totalGift: userGiftResult.total_gift

        }
        hostArray.push(hostInnerArray);
      }
      /// sub host

      var query = "SELECT user.id,user.username,user.image from user where user.id =" + resultLiveCallBattle.host_user_id;
      var userHosts = await getDbData(query);
      if (userHosts.length) {
        var query = "SELECT sum(coin) as total_coin, count(id) as total_gift from gift_history where gift_history.reciever_id =" + resultLiveCallBattle.host_user_id + " and gift_history.battle_id =" + resultLiveCallBattle.id;
        var userGiftResults = await getDbData(query);
        var userGiftResult = userGiftResults[0];
        var totalCoin = Math.floor(userGiftResult.total_coin);

        var userHost = userHosts[0];
        if (userHost.image == null) {
          userHost.image = 'default.png';
        }

        var userImageUrl = STORAGE_URL + '/user/' + userHost.image;
       
        var hostInnerArray = {
          userId: userHost.id,
          username: userHost.username,
          userImageUrl: userImageUrl,
          liveCallId: liveCallId,
          battleId: resultLiveCallBattle.id,
          isSuperHost: 0,
          totalCoin: totalCoin,
          totalGift: userGiftResult.total_gift
        }
        hostArray.push(hostInnerArray);
      }
    }
    battleInfo.battleDetail = battleDetail;
    battleInfo.liveBattleHosts = hostArray;

    return battleInfo;
  }
  async function getBattleDetail(data) {
    var battleId = data.battleId;
    var liveCallId = data.liveCallId;

    var battleInfo = {}

    var battleDetail = {};
    var hostArray = []
    var query = "SELECT  user_live_battle.id , user_live_battle.super_host_user_id , user_live_battle.host_user_id,start_time,end_time,total_time,total_allowed_time,status from user_live_battle  where  user_live_battle.id =" + battleId;
    var resultLiveCallBattles = await getDbData(query);

    if (resultLiveCallBattles.length) {


      var resultLiveCallBattle = resultLiveCallBattles[0];
      battleDetail = resultLiveCallBattle;
      var totalOngoingTime = 0;
      var timeToEnd = 0;

      if (resultLiveCallBattle.status == STATUS_LIVE_CALL_BATTLE_ONGOING) {
        let currentTime = Math.round((new Date()).getTime() / 1000);
        totalOngoingTime = currentTime - battleDetail.start_time;
        timeToEnd = battleDetail.total_allowed_time - totalOngoingTime;
      } else if (resultLiveCallBattle.status == STATUS_LIVE_CALL_BATTLE_PENDING) {
        totalOngoingTime = 0;
        timeToEnd = battleDetail.total_allowed_time;
      }

      battleDetail.totalOngoingTime = totalOngoingTime;
      battleDetail.timeToEnd = timeToEnd;

      //super host
      var query = "SELECT user.id,user.username, user.image from user where user.id =" + resultLiveCallBattle.super_host_user_id;
      var userSuperHosts = await getDbData(query);
      if (userSuperHosts.length) {
        var userSuperHost = userSuperHosts[0];

        var query = "SELECT sum(coin) as total_coin, count(id) as total_gift from gift_history where gift_history.reciever_id =" + resultLiveCallBattle.super_host_user_id + " and gift_history.battle_id =" + resultLiveCallBattle.id;
        var userGiftResults = await getDbData(query);

        var userGiftResult = userGiftResults[0];

        if (userSuperHost.image == null) {
          userSuperHost.image = 'default.png';
        }

        var userImageUrl = STORAGE_URL + '/user/' + userSuperHost.image;
        if (userSuperHost.image == null) {
          userImageUrl = null;
        }

        var hostInnerArray = {
          userId: userSuperHost.id,
          username: userSuperHost.username,
          userImageUrl: userImageUrl,
          liveCallId: liveCallId,
          battleId: resultLiveCallBattle.id,
          isSuperHost: 1,
          totalCoin: userGiftResult.total_coin,
          totalGift: userGiftResult.total_gift

        }
        hostArray.push(hostInnerArray);
      }
      /// sub host

      var query = "SELECT user.id,user.username, user.image from user where user.id =" + resultLiveCallBattle.host_user_id;
      var userHosts = await getDbData(query);
      if (userHosts.length) {
        var query = "SELECT sum(coin) as total_coin, count(id) as total_gift from gift_history where gift_history.reciever_id =" + resultLiveCallBattle.host_user_id + " and gift_history.battle_id =" + resultLiveCallBattle.id;
        var userGiftResults = await getDbData(query);
        var userGiftResult = userGiftResults[0];

        var userHost = userHosts[0];

        if (userHost.image == null) {
          userHost.image = 'default.png';
        }
        var userImageUrl = STORAGE_URL + '/user/' + userHost.image;
        if (userHost.image == null) {
          userImageUrl = null;
        }
        var hostInnerArray = {
          userId: userHost.id,
          username: userHost.username,
          userImageUrl: userImageUrl,
          liveCallId: liveCallId,
          battleId: resultLiveCallBattle.id,
          isSuperHost: 0,
          totalCoin: userGiftResult.total_coin,
          totalGift: userGiftResult.total_gift
        }
        hostArray.push(hostInnerArray);
      }
    }
    battleInfo.battleDetail = battleDetail;
    battleInfo.liveBattleHosts = hostArray;

    return battleInfo;
  }
  async function getLiveCallUserCount(data) {
    var liveCallId = data.liveCallId;
    var query = "SELECT  count(live_call_viewer.id) as total_user,live_call_id from live_call_viewer where is_ban=0 and live_call_viewer.live_call_id =" + liveCallId + " group by live_call_id";
    var totalUserResult = await getDbData(query);
    var totalUserCount = 0;
    if (totalUserResult.length) {
      totalUserCount = totalUserResult[0]['total_user'];
    }
    return totalUserCount;
  }
  server.listen(config.port, () => {
    console.log('listening on *:' + config.port);
  });
});
