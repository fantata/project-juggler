// 1:1 WebRTC call, signalled over the Reverb 'call' presence channel via client
// whispers (offer / answer / ICE). STUN-only for now — if a real call won't
// connect on your networks, add a TURN relay to ICE_SERVERS.
//
// Note: this is the first cut. WebRTC always needs real two-browser testing
// (with `php artisan reverb:start` running) to shake out — it can't be unit-tested.

const ICE_SERVERS = [{ urls: 'stun:stun.l.google.com:19302' }];

document.addEventListener('alpine:init', () => {
    window.Alpine.data('callWidget', (selfId) => ({
        // idle · calling · ringing · connecting · in-call
        state: 'idle',
        selfId,
        otherPresent: false,
        otherName: null,
        muted: false,
        videoOn: true,
        error: null,

        pc: null,
        localStream: null,
        channel: null,
        pendingOffer: null,

        init() {
            if (! window.Echo) {
                return; // Reverb not running — widget stays dormant.
            }

            this.channel = window.Echo.join('call')
                .here((members) => this.setPresence(members))
                .joining((m) => { if (m.id !== this.selfId) { this.otherPresent = true; this.otherName = m.name; } })
                .leaving((m) => { if (m.id !== this.selfId) { this.otherPresent = false; this.otherName = null; if (this.state !== 'idle') this.endCall(); } })
                .listenForWhisper('offer', (e) => this.onOffer(e))
                .listenForWhisper('answer', (e) => this.onAnswer(e))
                .listenForWhisper('ice', (e) => this.onIce(e))
                .listenForWhisper('decline', () => this.reset())
                .listenForWhisper('hangup', () => this.endCall());
        },

        setPresence(members) {
            const other = members.find((m) => m.id !== this.selfId);
            this.otherPresent = !! other;
            this.otherName = other?.name ?? null;
        },

        // ── Outgoing ────────────────────────────────────────────────
        async startCall() {
            try {
                this.error = null;
                await this.getMedia();
                this.makePeer();
                const offer = await this.pc.createOffer();
                await this.pc.setLocalDescription(offer);
                this.channel.whisper('offer', { sdp: this.pc.localDescription });
                this.state = 'calling';
            } catch (e) {
                this.error = 'Could not start the call — check mic/camera permission.';
                this.reset();
            }
        },

        async onAnswer(e) {
            if (! this.pc) return;
            await this.pc.setRemoteDescription(new RTCSessionDescription(e.sdp));
            this.state = 'connecting';
        },

        // ── Incoming ────────────────────────────────────────────────
        onOffer(e) {
            this.pendingOffer = e.sdp;
            this.state = 'ringing';
        },

        async accept() {
            try {
                await this.getMedia();
                this.makePeer();
                await this.pc.setRemoteDescription(new RTCSessionDescription(this.pendingOffer));
                const answer = await this.pc.createAnswer();
                await this.pc.setLocalDescription(answer);
                this.channel.whisper('answer', { sdp: this.pc.localDescription });
                this.pendingOffer = null;
                this.state = 'connecting';
            } catch (e) {
                this.error = 'Could not answer — check mic/camera permission.';
                this.reset();
            }
        },

        decline() {
            this.channel?.whisper('decline', {});
            this.reset();
        },

        async onIce(e) {
            if (! this.pc || ! e.candidate) return;
            try {
                await this.pc.addIceCandidate(new RTCIceCandidate(e.candidate));
            } catch (_) { /* candidate can arrive before remote desc; ignore */ }
        },

        // ── Plumbing ────────────────────────────────────────────────
        makePeer() {
            this.pc = new RTCPeerConnection({ iceServers: ICE_SERVERS });

            this.localStream.getTracks().forEach((t) => this.pc.addTrack(t, this.localStream));

            this.pc.onicecandidate = (ev) => {
                if (ev.candidate) this.channel.whisper('ice', { candidate: ev.candidate });
            };
            this.pc.ontrack = (ev) => {
                if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = ev.streams[0];
            };
            this.pc.onconnectionstatechange = () => {
                const s = this.pc?.connectionState;
                if (s === 'connected') this.state = 'in-call';
                if (s === 'failed' || s === 'disconnected' || s === 'closed') this.endCall();
            };
        },

        async getMedia() {
            this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: this.videoOn });
            if (this.$refs.localVideo) this.$refs.localVideo.srcObject = this.localStream;
        },

        toggleMute() {
            this.muted = ! this.muted;
            this.localStream?.getAudioTracks().forEach((t) => { t.enabled = ! this.muted; });
        },

        toggleVideo() {
            this.videoOn = ! this.videoOn;
            this.localStream?.getVideoTracks().forEach((t) => { t.enabled = this.videoOn; });
        },

        hangup() {
            this.channel?.whisper('hangup', {});
            this.endCall();
        },

        endCall() {
            this.reset();
        },

        reset() {
            this.pc?.close();
            this.pc = null;
            this.localStream?.getTracks().forEach((t) => t.stop());
            this.localStream = null;
            this.pendingOffer = null;
            this.muted = false;
            this.state = 'idle';
            if (this.$refs.remoteVideo) this.$refs.remoteVideo.srcObject = null;
            if (this.$refs.localVideo) this.$refs.localVideo.srcObject = null;
        },
    }));
});
