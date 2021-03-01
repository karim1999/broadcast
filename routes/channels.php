<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//    return (int) $user->id === (int) $id;
//});
//
//Broadcast::channel('presence-send-message', function ($user, $id) {
//    return $user;
//});
Broadcast::channel('streaming-channel', function ($user) {
    return $user;
});
//
//Broadcast::channel('user.*', function ($user, $userId) {
//    return $user->id === $userId;
//});
//Broadcast::channel('user.{userId}', function ($user, $userId) {
//    if ($user->id === $userId) {
//        return array('name' => $user->name);
//    }
//});
