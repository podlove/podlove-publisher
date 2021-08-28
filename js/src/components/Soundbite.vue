<template>
    <div class="container">
        <div class="soundbite-input">
            <div>
                <label>Start</label>
                <input v-model="start" class="form-input" type="text" placeholder="00:00:00"/>
                <p v-if="isStartValid">The start timepoint has not the correct format. Please use HH:MM:SS</p>
            </div>
            <div class="soundbite-space">
                <label>End</label>
                <input v-model="end" class="form-input" type="text"  placeholder="00:00:00"/>
                <p v-if="isEndValid">The end timepoint has not the correct format. Please use HH:MM:SS</p>
            </div>
            <div class="soundbite-space">
                <label>Duration</label>
                <input v-model="duration" class="form-input" type="text"  placeholder="00:00:00"/>
                <p v-if="isDurationValid">The duration has not the correct format. Please use HH:MM:SS</p>
            </div>
        </div>
    </div>
</template>


<script>
export default {
  computed: {
      isStartValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.oldStart === this.start) {
                this.startValid = true;
                return false;
            }       

            if (/^\d\d:[0-5]\d:[0-5]\d$/.test(this.start)) {
                this.startValid = true;
                this.calculateEndTime();
                this.calculateDuration();
                this.sendDataToPodlove();
                return false;
            }
            else {
                this.startValid = false;
                return true;
            }
      },
      isEndValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.end === this.oldEnd) {
                this.endValid = true;
                return false;
            }

            if (/^\d\d:[0-5]\d:[0-5]\d$/.test(this.end)) {
                this.calculateDuration();
                this.endValid = true;
                this.sendDataToPodlove();
                this.oldEnd = this.end;
                return false;
            }
            else {
                this.endValid = false;
                return true;
            }
      },
      isDurationValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.duration === this.oldDuration) {
                this.durationValid = true;
                return false;
            }
            
            if (/^\d\d:[0-5]\d:[0-5]\d$/.test(this.duration)) {
                this.calculateEndTime();
                this.durationValid = true;
                this.sendDataToPodlove();
                this.oldDuration = this.duration;
                return false;
            }
            else {
                this.durationValid = false;
                return true;
            }
      }
  },
  methods: {
    sendDataToPodlove: function() {

        if (this.dataReadFinish === false)
            return;

        if (this.durationValid === false || this.startValid === false || this.endValid === false)
            return;
            
        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id; 
        this.axios
            .patch(url,
            {
                soundbite_start: this.start,
                soundbite_duration: this.duration
            }, 
            {
            headers: {
                'X-WP-Nonce': podlove_vue.nonce
            }
            })
            .then(({$data}) => {

            })
            .catch((error) => {
                alert("Daten konnten nicht eingetragen werden");
                console.log(error);
        });

        this.oldStart = this.start;
        this.oldEnd = this.end;
        this.oldDuration = this.duration;
    },
    getTimeAsSec: function(text) {
        // text should a string in the format HH:MM:SS
        if (typeof text === 'string') {
            let hour = text.substr(0,2);
            let min = text.substr(3,2);
            let sec = text.substr(6,2);
            let hourVal = parseInt(hour);
            let minVal = parseInt(min);
            let secVal = parseInt(sec);
            return hourVal*3600 + minVal*60 + secVal;
        }
        return 0;
    },
    getTimeAsString: function(val) {
        // val should an integer
        if (typeof val === 'number') {
            let hourVal = Math.trunc(val / 3600);
            val = val - hourVal * 3600;
            let minVal = Math.trunc(val / 60);
            let secVal = val - minVal * 60;
            let hour = hourVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            let min = minVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            let sec = secVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            return  hour.concat(':', min, ':' , sec);
        }
        return '00:00:00';
    },
    calculateEndTime: function() {
        let startSec = this.getTimeAsSec(this.start);
        let durationSec = this.getTimeAsSec(this.duration);
        let endSec = startSec + durationSec;
        this.end = this.getTimeAsString(endSec);
    },
    calculateDuration: function() {
        let startSec = this.getTimeAsSec(this.start);
        let endSec = this.getTimeAsSec(this.end);
        let durationSec = endSec - startSec;
        this.duration = this.getTimeAsString(durationSec);
    },
    getDataFromPodlove: async function() {
        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id;
        try {
            const response = await this.$http.get(url);
            this.start = response.data.soundbite_start;
            this.duration = response.data.soundbite_duration;
            this.calculateEndTime();
            this.dataReadFinish = true;
        }
        catch (error) {
            alert(error);
        }
    }
  },
  data () {
    return {
        start: '00:00:00',
        end: '00:00:00',
        duration: '00:00:00',
        oldStart: '00:00:00',
        oldEnd: '00:00:00',
        oldDuration: '00:00:00',
        startValid: false,
        durationValid: false,
        endValid: false,
        dataReadFinish: false,
    }
  },
  created() {
      this.getDataFromPodlove();
  }
}
</script>


<style scoped>
.soundbite-input {
    display: flex;
}

.soundbite-space {
    margin-left: 20px;
}

</style>