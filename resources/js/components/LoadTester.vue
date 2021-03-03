<template>
    <div class="flex flex-row items-center justify-between">
        <loading :active.sync="isLoading"
                 :can-cancel="false"></loading>
        <button @click="startRecording()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3">
            Start
        </button>
        <video
            ref="myVideo"
            class=""
            muted
            autoplay="autoplay" loop="loop"
        >
            <source src="/video.mkv" type="video/mp4">
        </video>
        <canvas
            style="display: none;"
            ref="myCanvas"
            id="canvas"></canvas>
    </div>
</template>

<script>
import Peer from "simple-peer";
// Import component
import Loading from 'vue-loading-overlay';
// Import stylesheet
import 'vue-loading-overlay/dist/vue-loading.css';

export default {
    props: {
        user: {
            required: true,
            type: Object
        }
    },
    data: function (){
        return {
            peersNum: 100,
            peers: [],
            isLoading: false,
            channel: null,
            myPeerData: null,
            currentPeer: null,
            myStream: null,
            isConnected: false,
            isBlurred: false,
            peerConfig: {
                iceServers: [{
                    "urls": ["stun:stun.cloudgear.dev:5349"],
                    "username": "guest",
                    "credential": "somepassword"
                }, {
                    "urls": ["turn:turn.cloudgear.dev:5349"],
                    "username": "guest",
                    "credential": "somepassword"
                }, {
                    "urls": ["turn:turn.cloudgear.dev:3478"],
                    "username": "guest",
                    "credential": "somepassword"
                }, {
                    "urls": ["stun:stun.cloudgear.dev:3478"],
                    "username": "guest",
                    "credential": "somepassword"
                }]
            },
        }
    },
    components: {
        Loading
    },
    async mounted() {
        console.log(this.user)
        this.channel = Echo.join('streaming-channel');
        this.channel.listen('.pusher:subscription_error', (err) => {
            console.log(err)
        });
        this.channel.listen('.pusher:subscription_succeeded', ()=> {
            console.log(this.channel)
            console.log("Done")
            for (var i=0; i < this.peersNum; i++){
                this.listenToConfirm(i);
            }
        });
        this.channel.listen('.client-ask-for-peers', (e) => {
            this.peers.map(peer => {
                if(peer.isConnected) {
                    return;
                }
                if(peer.peer){
                    this.sendPeer(peer.peerData)
                }else if(this.myStream){
                    this.createPeer(this.myStream)
                }
            })
        })
        this.startStreaming();
    },
    methods: {
        listenToConfirm(id){
            this.channel.listen('.client-signal-'+id, (data) => {
                if(data.type === "answer"){
                    console.log("signal from admin", data, id)
                    let peer= this.getPeerById(id);
                    if(peer){
                        console.log("Connecting........")
                        peer.peer.signal(data);
                    }
                }
            })
        },
        async startStreaming(){
            let canvas= this.$refs.myCanvas;
            var ctx = canvas.getContext('2d');
            let video= this.$refs.myVideo;
            let startTime;
            video.onresize = () => {
                console.log(`Remote video size changed to ${video.videoWidth}x${video.videoHeight}`);
                // We'll use the first onsize callback as an indication that video has started
                // playing out.
                let stream= canvas.captureStream();
                this.myStream= stream;
                this.createMultiplePeers(stream);
                if (startTime) {
                    const elapsedTime = window.performance.now() - startTime;
                    console.log(`Setup time: ${elapsedTime.toFixed(3)}ms`);
                    startTime = null;
                }
            };

            video.addEventListener('loadedmetadata', function() {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
            });

            video.addEventListener('play', function() {
                var $this = this; //cache
                (function loop() {
                    if (!$this.paused && !$this.ended) {
                        ctx.drawImage($this, 0, 0);
                        setTimeout(loop, 1000 / 30); // drawing at 30fps
                    }
                })();
            }, 0);
        },
        async startRecording(){
        },
        async createMultiplePeers(stream){
            for (var i=0; i < this.peersNum; i++){
                // await new Promise(resolve => setTimeout(resolve, 20000));
                this.createPeer(stream, i);
            }
        },
        getPeerById(id){
            return this.peers.find(peer => peer.id === id)
        },
        setPeerById(id, name, value){
            let peerData= this.getPeerById(id);
            if(peerData){
                peerData[name]= value;
            }
        },
        createPeer(stream, i){
            let currentPeer= new Peer({
                initiator: true,
                trickle: false,
                stream,
                config: this.peerConfig,
            });
            const peerObj= {
                id: i,
                socket_id: i,
                peer: currentPeer,
                peerData: null,
                isConnected: false,
            }
            this.peers.push(peerObj)
            this.attachEventListeners(currentPeer, i)
        },
        attachEventListeners(currentPeer, id){
            currentPeer.on("signal", (data) => {
                console.log("peer signal");
                console.log(data)
                this.setPeerById(id, "peerData", data)
                this.sendPeer(data, id)
            });

            currentPeer.on("connect", () => {
                console.log("peer connected");
                this.setPeerById(id, "isConnected", true)
            });

            currentPeer.on("error", (err) => {
                console.log(err);
            });

            currentPeer.on("close", () => {
                console.log("call closed caller");
                let peer= this.getPeerById(id)
                if(peer){
                    peer.peer.destroy();
                    peer.isConnected= false;
                    peer.peer= null
                }
            });
            currentPeer.on("stream", (stream) => {
                console.log("Streaming from peer......")
            });
        },
        sendPeer(data, id){
            this.sendMessage({type: "peer", user: {...this.user, id, socket_id: id}, peer: data})
        },
        sendMessage(data){
            console.log("Sending", data)
            this.channel.whisper('send-message', data);
        },
        sendToggleBlur(blur){
            this.channel.whisper('toggle-blur', {user: {...this.user, socket_id: Echo.socketId()}, blur});
        },
    }
}
</script>
