<template>
    <div class="p-5">
        <div class="grid grid-flow-row grid-cols-3 grid-rows-3 gap-4">
            <div id="my-video-chat-window"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'video-chat',
    props: {
        admin: {
            type: Boolean,
            default: false,
            required: false
        }
    },
    data: function () {
        return {
            videoTrack: null,
            accessToken: '',
            room: null,
            videoChatWindow: null,
            videoSettings: {
                name:'soccer',
                audio: true,
                video: { frameRate: 24, width: 300 },
                bandwidthProfile: {
                    video: {
                        mode: 'grid',
                        maxTracks: 10,
                        renderDimensions: {
                            high: {height:1080, width:1920},
                            standard: {height:720, width:1280},
                            low: {height:176, width:144}
                        }
                    }
                },
                maxAudioBitrate: 16000, //For music remove this line
                //For multiparty rooms (participants>=3) uncomment the line below
                preferredVideoCodecs: [{ codec: 'VP8', simulcast: true }],
                networkQuality: {local:1, remote: 1}
            }
        }
    },
    methods : {
        getAccessToken : function () {

            const _this = this
            const axios = require('axios')

            // Request a new token
            axios.get('/api/access_token')
                .then(function (response) {
                    _this.accessToken = response.data
                })
                .catch(function (error) {
                    console.log(error);
                })
                .then(function () {
                    _this.connectToRoom()
                });
        },
        connectToRoom : async function () {

            const { connect, createLocalVideoTrack, isSupported } = require('twilio-video');

            if (isSupported) {
                // Set up your video app.
            } else {
                alert('This browser is not supported by twilio-video.js.');
            }
            connect( this.accessToken, this.videoSettings).then(room => {
                this.room= room;

                console.log(`Successfully joined a Room: ${room}`);

                room.participants.forEach(this.participantConnected);
                room.on('participantConnected', this.participantConnected);

                room.on('participantDisconnected', this.participantDisconnected);
                room.once('disconnected', error => room.participants.forEach(this.participantDisconnected));

                if(!this.admin){
                    createLocalVideoTrack().then(track => {
                        this.videoChatWindow.appendChild(track.attach());
                        this.videoTrack= track;
                    });
                }
            }, error => {
                console.error(`Unable to connect to Room: ${error.message}`);
            });
        },
        appendVideo(div, video){
            if(this.admin){
                this.videoChatWindow.appendChild(div)
                div.appendChild(video)
            }
        },
        removeVideo(participant){
            console.log("removing...", participant.sid)
            if(document.getElementById(participant.sid)){
                document.getElementById(participant.sid).remove();
                console.log("done removing")
            }
        },
        participantConnected(participant) {
            const div = document.createElement('div');
            div.id = participant.sid;
            participant.tracks.forEach(publication => {
                if (publication.isSubscribed) {
                    const track = publication.track;
                    this.appendVideo(div, track.attach())
                }
            });

            participant.on('trackSubscribed', track => {
                console.log("trackSubscribed", track)
                this.appendVideo(div, track.attach())
            });

            participant.on('trackRemoved', (track)=>{
                console.log("trackRemoved", track)
                track.detach().forEach( function(element) { element.remove() });
            })
        },
        participantDisconnected(participant) {
            console.log('Participant "%s" disconnected', participant.sid);
            if(this.admin){
                this.removeVideo(participant)
            }
        },

        unsubscribeRoom(){
            if(this.videoTrack){
                this.videoTrack.stop();
            }
            this.removeVideo(this.room.localParticipant)
            this.room.disconnect()
        }
    },
    mounted : function () {
        console.log('Video chat room loading...')
        this.videoChatWindow = document.getElementById('my-video-chat-window');
        this.getAccessToken()
    },
    beforeDestroy : function () {
        console.log('Video chat room destroyed...')
        this.unsubscribeRoom()
    }

}
</script>
