<template>
    <div class="flex flex-row items-center justify-between">
        <loading :active.sync="isLoading"
                 :can-cancel="false"></loading>
        <button @click="startFaceApi()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3">
            Start
        </button>
        <video
            ref="myVideo"
            class=""
            playsinline
            muted
            autoplay
            v-show="myStream"
        />
    </div>
</template>

<script>
import Peer from "simple-peer";
import { getPermissions } from "./../videoAccess";
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
                isLoading: false,
                channel: null,
                myPeerData: null,
                currentPeer: null,
                myStream: null,
                isConnected: false,
                isBlurred: false,
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
                this.channel.listen('.client-signal-'+Echo.socketId(), (data) => {
                    if(data.type === "answer"){
                        console.log("signal from admin", data)
                        this.currentPeer.signal(data);
                    }
                })
            });
            this.channel.listen('.client-ask-for-peers', (e) => {
                console.log(e);
                if(this.isConnected) {
                    return;
                }
                if(this.currentPeer){
                    this.sendPeer(this.myPeerData)
                }else if(this.myStream){
                    this.createPeer(this.myStream)
                }
            })
        },
        methods: {
            async startFaceApi(){
                this.isLoading= true
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('models'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('models'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('models'),
                    faceapi.nets.faceExpressionNet.loadFromUri('models')
                ]).then(this.startRecording).then(res => {
                    this.$refs.myVideo.addEventListener('play', () => {
                        let video= this.$refs.myVideo;
                        const canvas = faceapi.createCanvasFromMedia(video)
                        document.body.append(canvas)
                        const displaySize = { width: video.width, height: video.height }
                        faceapi.matchDimensions(canvas, displaySize)
                        setInterval(async () => {
                            const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceExpressions()
                            if(!detections.length){
                                if(!this.isBlurred){
                                    // video.classList.add("blur");
                                    this.sendToggleBlur(true)
                                    this.isBlurred= true;
                                }
                            }
                            else{
                                if(this.isBlurred){
                                    // video.classList.remove("blur");
                                    this.sendToggleBlur(false)
                                    this.isBlurred= false;
                                }
                            }
                        }, 1000)
                    })
                }).catch(err => {
                    console.log(err)
                }).finally(res => {
                    this.isLoading= false
                })
            },
            async startRecording(){
                await this.getMediaPermission().then(stream => {
                    this.createPeer(stream);
                })
            },
            createPeer(stream){
                this.currentPeer = new Peer({
                    initiator: true,
                    trickle: false,
                    stream,
                    config: this.peerConfig,
                });
                this.attachEventListeners(this.currentPeer)
            },
            attachEventListeners(currentPeer){
                currentPeer.on("signal", (data) => {
                    console.log("peer signal");
                    console.log(data)
                    this.myPeerData= data
                    this.sendPeer(data)
                });

                currentPeer.on("connect", () => {
                    console.log("peer connected");
                    this.isConnected= true;
                });

                currentPeer.on("error", (err) => {
                    console.log(err);
                });

                currentPeer.on("close", () => {
                    console.log("call closed caller");
                    currentPeer.destroy();
                    this.currentPeer= null;
                    this.isConnected= false;
                });
                currentPeer.on("stream", (stream) => {
                    console.log("Streaming from peer......")
                });

            },
            getMediaPermission() {
                return getPermissions()
                    .then((stream) => {
                        console.log({stream})
                        this.myStream = stream;
                        if (this.$refs.myVideo) {
                            if ('srcObject' in this.$refs.myVideo) {
                                this.$refs.myVideo.srcObject = stream
                            } else {
                                this.$refs.myVideo.src = window.URL.createObjectURL(stream)
                            }
                        }
                        return stream;
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            },
            sendPeer(data){
                this.sendMessage({type: "peer", user: {...this.user, socket_id: Echo.socketId()}, peer: data})
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
