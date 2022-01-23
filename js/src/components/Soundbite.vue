<template>
    <div class="container">
        <div class="soundbite-input">
            <div class="soudbite-formitem">
                <label>Start</label>
                <input v-model="start" class="form-input" type="text" placeholder="00:00:00"/>
                <p v-if="isStartNotValid">The start timepoint has not the correct format. Please use HH:MM:SS</p>
            </div>
            <div class="soundbite-space"></div>
            <div class="soudbite-formitem">
                <label>End</label>
                <input v-model="end" class="form-input" type="text"  placeholder="00:00:00"/>
                <p v-if="isEndNotValid">The end timepoint has not the correct format. Please use HH:MM:SS</p>
                <p v-if="isEndNotGreater"> The end timepoint must greater than the start timepoint</p>
            </div>
            <div class="soundbite-space"></div>
            <div class="soudbite-formitem">
                <label>Duration</label>
                <input v-model="duration" class="form-input" type="text"  placeholder="00:00:00"/>
                <p v-if="isDurationNotValid">The duration has not the correct format. Please use HH:MM:SS</p>
            </div>
            <div class="soundbite-space"></div>
            <div class="soudbite-formitem">
                <label>Soundbite title</label>
                <input v-model="title" class="form-input" type="text"  placeholder="optional" @focusout="sendTitleToPodlove"/>
            </div>
            <div class="soundbite-space"></div>
            <div class="soundbite-button">
                <button class="button" type="button" @click="clearData">
                    Clear
                </button>
            </div>
        </div>
    </div>
</template>


<script>
export default {
  computed: {
      isStartNotValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.oldStart === this.start) {
                this.startValid = true;
                return false;
            }

            // keine Eingabe erfolgt
            if (this.start === null) {
                this.startValid = false;
                return false;
            }

            if (this.isNormalTimeFieldValid(this.start)) {
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
      isEndNotGreater: function() {
            if (this.startValid === false || this.endValid === false)
                return false;

            // keine Eingabe erfolgt
            if (this.start === null || this.duration === null)
                return false;

            let startSec = this.getTimeAsMilliSec(this.start);
            let endSec = this.getTimeAsMilliSec(this.end);

            if (startSec === 0 && endSec === 0)
                return false;

            if (startSec >= endSec)
                return true;

            this.endValid = false;
            return false;
      },
      isEndNotValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.end === this.oldEnd) {
                this.endValid = true;
                return false;
            }

            // Default-Wert ist okay
            if (this.end === null || this.end === '00:00:00') {
                this.endValid = true;
                return false;
            }

            if (this.isNormalTimeFieldValid(this.end)) {
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
      isDurationNotValid: function() {
            // Hat sich der Wert nicht geaendert, so ist kein Pruefung noetig
            if (this.duration === this.oldDuration) {
                this.durationValid = true;
                return false;
            }

            // keine Eingabe erfolgt
            if (this.duration === null) {
                return false;
            }

            if (this.isNormalTimeFieldValid(this.duration)) {
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
                soundbite_duration: this.duration,
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
    sendTitleToPodlove: function() {
        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id;
        this.axios
            .patch(url,
            {
                soundbite_title: this.title
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

    },
    clearData: function() {
        this.start = '00:00:00';
        this.end = '00:00:00';
        this.duration = '00:00:00';
        this.title = '';

        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id;
        this.axios
            .patch(url,
            {
                soundbite_start: this.start,
                soundbite_duration: this.duration,
                soundbite_title: this.title
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
        this.oldDuration = this.duration;
        this.oldEnd = this.end;
        this.oldTitle = this.title;

    },
    getTimeAsMilliSec: function(text) {
        // text should a string in the format HH:MM:SS.mmm
        if (typeof text === 'string') {
            let npt = require('normalplaytime');
            let ms = npt.parse(text);
            if (ms === null)
                return 0;
            return ms;
        }
        return 0;
    },
    getTimeAsString: function(val) {
        // val should an integer
        if (typeof val === 'number') {
            let hourVal = Math.trunc(val / 3600000);
            val = val - hourVal * 3600000;
            let minVal = Math.trunc(val / 60000);
            val = val - minVal * 60000;
            let secVal = Math.trunc(val / 1000);
            let msVal = val - secVal*1000;
            let hour = hourVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            let min = minVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            let sec = secVal.toLocaleString('en-US', {minimumIntegerDigits: 2, useGrouping: false});
            if (msVal > 0) {
                if (msVal % 100 === 0)
                    msVal = msVal / 100;
                else if (msVal % 10 === 0)
                    msVal = msVal / 10;
                let ms = msVal.toLocaleString('en-US', {minimumIntegerDigits: 1, useGrouping: false});
                return  hour.concat(':', min, ':' , sec, '.', ms);
            }
            return  hour.concat(':', min, ':' , sec);
        }
        return '00:00:00';
    },
    calculateEndTime: function() {
        let startSec = this.getTimeAsMilliSec(this.start);
        let durationSec = this.getTimeAsMilliSec(this.duration);
        let endSec = startSec + durationSec;
        if (endSec <= 0 || isNaN(endSec))
            this.end = this.start;
        else
            this.end = this.getTimeAsString(endSec);
    },
    calculateDuration: function() {
        let startSec = this.getTimeAsMilliSec(this.start);
        let endSec = this.getTimeAsMilliSec(this.end);
        let durationSec = endSec - startSec;
        if (durationSec < 0 || isNaN(durationSec))
            this.duration = '00:00:00';
        else
            this.duration = this.getTimeAsString(durationSec);
    },
    getDataFromPodlove: async function() {
        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id;
        try {
            const response = await this.$http.get(url);
            this.start = response.data.soundbite_start;
            this.duration = response.data.soundbite_duration;
            this.title = response.data.soundbite_title;
            this.calculateEndTime();
            this.dataReadFinish = true;
        }
        catch (error) {
            alert(error);
        }
    },
    isNormalTimeFieldValid: function(text) {
        if (typeof text === 'string') {
            let npt = require('normalplaytime');
            let ms = npt.parse(text);
            if (ms === null)
                return false;
            return true;
        }
        return false;
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
        title: '',
        oldTitle: '',
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
    max-width: 800px;
}

.soudbite-formitem {
    flex: 1 1 0%;
}

.soundbite-space {
    width: 20px;
}

.soundbite-button {
    display: grid;
    align-content: end;
}
</style>
