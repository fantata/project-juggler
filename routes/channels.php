<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Presence channel for the 1:1 WebRTC call. Both authenticated users join,
// see each other's presence, and whisper the connection handshake
// (offer / answer / ICE candidates) peer-to-peer over it.
Broadcast::channel('call', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
