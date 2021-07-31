<template>
    <div class="container">
        <div class="soundbite-input">
            <div>
                <label>Start</label>
                <input v-model="start" class="form-input" type="text" placeholder="00:00:00"/>
                <p v-if="isStartValid">The start timepoint has not the correct format. Please use HH:MM:SS</p>
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
            if (/^\d\d:\d\d:\d\d$/.test(this.start)) {
                this.startValid = true;
                this.sendDataToPodlove();
                return false;
            }
            else {
                this.startValid = false;
                return true;
            }
      },
      isDurationValid: function() {
            if (/^\d\d:\d\d:\d\d$/.test(this.duration)) {
                this.durationValid = true;
                this.sendDataToPodlove();
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

        if (this.durationValid === false || this.startValid === false)
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
    },
    getDataFromPodlove: async function() {
        let url = podlove_vue.rest_url + 'podlove/v1/episodes/' + podlove_vue.episode_id;
        try {
            const response = await this.$http.get(url);
            this.start = response.data.soundbite_start;
            this.duration = response.data.soundbite_duration;
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
        duration: '00:00:00',
        startValid: false,
        durationValid: false,
        dataReadFinish: false
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