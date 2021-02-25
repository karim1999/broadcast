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
            accessToken: ''
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

            const { connect, createLocalVideoTrack } = require('twilio-video');

            connect( this.accessToken, { name:'soccer' }).then(room => {

                console.log(`Successfully joined a Room: ${room}`);

                const videoChatWindow = document.getElementById('my-video-chat-window');

                if(!this.admin){
                    createLocalVideoTrack().then(track => {
                        videoChatWindow.appendChild(track.attach());
                    });
                }

                if(this.admin){
                    room.participants.forEach(participant => {
                        console.log('Participant "%s"', participant.sid);
                        if(this.admin){
                            this.participantListeners(participant, videoChatWindow)
                        }
                    });

                    // room.on('participantConnected', participant => {
                    //     console.log(`A remote Participant connected: ${participant}`);
                    // });

                    room.on('participantDisconnected', participant => {
                        console.log('Participant "%s" disconnected', participant.sid);
                        this.removeVideo(participant)
                    })
                    room.on('participantConnected', participant => {
                        console.log(`Participant "${participant.sid}" connected`);

                        this.participantListeners(participant, videoChatWindow)
                    });
                }
            }, error => {
                console.error(`Unable to connect to Room: ${error.message}`);
            });
        },
        participantListeners(participant, videoChatWindow){
            const div = document.createElement('div');
            div.id = participant.sid;
            participant.tracks.forEach(publication => {
                if (publication.isSubscribed) {
                    const track = publication.track;
                    this.appendVideo(div, videoChatWindow, track.attach())
                    // videoChatWindow.appendChild(track.attach());
                }
            });

            participant.on('trackSubscribed', track => {
                this.appendVideo(div, videoChatWindow, track.attach())
                // videoChatWindow.appendChild(track.attach());
            });
            participant.on('trackRemoved', (track)=>{
                track.detach().forEach( function(element) { element.remove() });
            })
        },
        appendVideo(div, videoChatWindow, video){
            // div.innerText = participant.identity;
            videoChatWindow.appendChild(div)
            div.appendChild(video)
        },
        removeVideo(participant){
            console.log("removing...", participant.sid)
            if(document.getElementById(participant.sid)){
                document.getElementById(participant.sid).remove();
                console.log("done removing")
            }
            // participant.tracks.forEach((track)=>{
            //     track.detach().forEach( function(element) { element.remove() });
            // });
        }
    },
    mounted : function () {
        console.log('Video chat room loading...')

        this.getAccessToken()
    }
}
</script>
