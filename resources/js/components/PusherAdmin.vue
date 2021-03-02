<template>
    <div class="container flex flex-row content-start justify-around">
        <div class="flex-1" style="flex-basis: 5%" v-for="peer in peers">
            <video
                :id="'video_'+peer.user.socket_id"
                muted
                autoplay
                playsinline
            />
        </div>
    </div>
</template>

<script>
import Peer from "simple-peer";
import { getPermissions } from "./../videoAccess";

    export default {
        props: {
            user: {
                required: true,
                type: Object
            }
        },
        data: function (){
            return {
                channel: null,
                peers: [],
                peerConfig: {
                    iceServers: [
                        {url:'stun:stun01.sipphone.com'},
                        {url:'stun:stun.ekiga.net'},
                        {url:'stun:stun.fwdnet.net'},
                        {url:'stun:stun.ideasip.com'},
                        {url:'stun:stun.iptel.org'},
                        {url:'stun:stun.rixtelecom.se'},
                        {url:'stun:stun.schlund.de'},
                        {url:'stun:stun.l.google.com:19302'},
                        {url:'stun:stun1.l.google.com:19302'},
                        {url:'stun:stun2.l.google.com:19302'},
                        {url:'stun:stun3.l.google.com:19302'},
                        {url:'stun:stun4.l.google.com:19302'},
                        {url:'stun:stunserver.org'},
                        {url:'stun:stun.softjoys.com'},
                        {url:'stun:stun.voiparound.com'},
                        {url:'stun:stun.voipbuster.com'},
                        {url:'stun:stun.voipstunt.com'},
                        {url:'stun:stun.voxgratia.org'},
                        {url:'stun:stun.xten.com'},
                        {
                            url: 'turn:numb.viagenie.ca',
                            credential: 'muazkh',
                            username: 'webrtc@live.com'
                        },
                        {
                            url: 'turn:192.158.29.39:3478?transport=udp',
                            credential: 'JZEOEt2V3Qb0y27GRntt2u2PAYA=',
                            username: '28224511:1379330808'
                        },
                        {
                            url: 'turn:192.158.29.39:3478?transport=tcp',
                            credential: 'JZEOEt2V3Qb0y27GRntt2u2PAYA=',
                            username: '28224511:1379330808'
                        }                    ]
                },
            }
        },
        async mounted() {
            this.channel = Echo.join('streaming-channel');
            this.channel.listen('.pusher:subscription_error', (err) => {
                console.log(err)
            });
            this.channel.listen('.pusher:subscription_succeeded', () => {
                this.startListening();
                console.log("Done")
            });
            this.channel.joining((member) =>{
                // for example
                console.log("Member "+member.id+ " has just joined.")
                console.log(member)
            })
            this.channel.leaving((member) =>{
                // for example
                console.log("Member "+member.id+ " has just left.")
                console.log(member)
            })
            this.channel.listen('.client-send-message', (e) => {
                console.log(e);
                if(!e)
                    return;
                if(e.type === "peer"){
                    if(e.peer && e.user){
                        this.addPeer(e.user, e.peer);
                    }
                }
            })
            this.channel.listen('.client-toggle-blur', (e) => {
                console.log(e);
                if(!e || !e.user)
                    return;

                let videoObj= document.getElementById('video_'+e.user.socket_id)

                if(!videoObj)
                    return ;
                if(e.blur){
                    videoObj.classList.add("blur");
                }else{
                    videoObj.classList.remove("blur");
                }
            })
        },
        methods: {
            startListening(){
                this.askForPeers("Admin "+this.user.id+" has started listening.")
            },
            addPeer(user, peerData){
                console.log("Adding...")
                var peer = new Peer({trickle: false, config: this.peerConfig});
                const newPeer= {
                    user,
                    peerData,
                    peer,
                    stream: null
                }
                this.attachEventListeners(peer, user)
                peer.signal(peerData)
                this.peers.push(newPeer)
            },
            attachEventListeners(peer, user= null){
                peer.on("signal", (data) => {
                    console.log("peer signal");
                    console.log(data)
                    this.sendSignal(user, data)
                });

                peer.on("connect", () => {
                    console.log("peer connected");
                });

                peer.on("error", (err) => {
                    console.log(err);
                });

                peer.on("close", () => {
                    console.log("call closed caller");
                    this.peers= this.peers.filter(p => p && p.user && p.user.socket_id !== user.socket_id)
                    peer.destroy();
                });
                peer.on("stream", (stream) => {
                    console.log("call streaming");
                    console.log(stream)
                    console.log(user.socket_id)
                    let videoObj= document.getElementById('video_'+user.socket_id)
                    console.log(videoObj)
                    if ('srcObject' in videoObj) {
                        videoObj.srcObject = stream
                    } else {
                        videoObj.src = window.URL.createObjectURL(stream)
                    }
                });
           },
            sendMessage(msg){
                this.channel.whisper('send-msg', msg);
            },
            askForPeers(data){
                this.channel.whisper('ask-for-peers', data);
            },
            sendSignal(user, data){
                this.channel.whisper('signal-'+user.socket_id, data);
            }
        }
    }
</script>
