<template>
    <div class="container">
       
        <div class="row">

            <div class="transcripts-tab-wrapper">
                
                <a href="#" 
                   class="transcripts-tab" 
                   :class="{'transcripts-tab-active': mode === 'voices'}" 
                   @click.prevent="mode = 'voices'">
                   Voices
                </a>

                <a href="#" 
                   class="transcripts-tab" 
                   :class="{'transcripts-tab-active': mode === 'import'}" 
                   @click.prevent="mode = 'import'">
                   Import
                </a>

                <a href="#" 
                   class="transcripts-tab" 
                   :class="{'transcripts-tab-active': mode === 'export'}" 
                   @click.prevent="mode = 'export'">
                   Export
                </a>

            </div>

        </div>

        <div class="row voices" v-show="mode == 'voices'">
            <div class="voice col-md-12" v-for="(voice, index) in voices">
                <div class="voice-label">
                    {{ voice.label }}
                </div>
                <div class="voice-assignment">
                    <v-select :options="contributorsOptions" v-model="voice.option" style="width: 300px">
                        <template slot="option" slot-scope="option">
                            <img :src="option.avatar" width="16" height="16" />
                            {{ option.label }}
                        </template>                          
                    </v-select>
                    <input type="hidden" :name="'_podlove_meta[transcript_voice][' + voice.label + ']'" :value="voice.option.value" v-if="voice.option">
                </div>
            </div>
        </div>  

        <div class="row import" v-show="mode == 'import'">
            <p>
                <form>
                    <button class="button button-primary" @click.prevent="initImportTranscript">Import Transcript</button>
                    <input type="file" name="transcriptimport" id="transcriptimport" @change="importTranscript" style="display: none" :disabled="importing"> 
                    <div class="description">{{ description }}</div>
                </form>
            </p>
        </div>  

        <div class="row export" v-show="mode == 'export'">
            <p>
                <a class="button button-secondary" :href="webvttDownloadHref" download="transcript.webvtt">Export webvtt</a>
                <a class="button button-secondary" :href="jsonDownloadHref" download="transcript.json">Export json</a> 
            </p>
        </div>

    </div>
</template>

<script type="text/javascript">
export default {
    data() {
        return {
            mode: 'voices',
            importing: false,
            lastError: '',
            voices: null,
            contributors: null
        }
    },

    computed: {
        description: function() {
            if (this.importing) {
                return "importing ..."
            } else if (this.lastError) {
                return this.lastError
            } else {
                return "Accepts: WebVTT"
            }
        },
        contributorsOptions: function() {
            return this.contributors.map((c) => {
                return {label: c.name, value: c.id, avatar: c.avatar}
            })
        },
        webvttDownloadHref: function() {
            const post_id = document.querySelector('#post_ID').value
            const host = window.location.hostname;

            return "//" + host + "?p=" + post_id + "&podlove_transcript=webvtt"
        },
        jsonDownloadHref: function() {
            const post_id = document.querySelector('#post_ID').value
            const host = window.location.hostname;

            return "//" + host + "?p=" + post_id + "&podlove_transcript=json"
        }
    },

    methods: {
        initImportTranscript () {
            const fileInput = document.getElementById("transcriptimport");
            fileInput.click();
        },
        importTranscript() {
            const fileInput = document.getElementById("transcriptimport");
            const file = fileInput.files[0];

            this.importing = true;
            
            let form = new FormData()
            form.append('transcript', file)
            form.append('action', 'podlove_transcript_import')
            form.append('post_id', document.querySelector('#post_ID').value)

            this.axios.post(ajaxurl, form, {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
            }).then(({data}) => {
                if (data.error) {
                    this.lastError = data.error
                } else {
                    this.fetchVoices()
                    this.mode = 'voices';
                }
                this.importing = false;
                fileInput.parentElement.reset();
            }).catch((error) => {
                this.importing = false;
                fileInput.parentElement.reset();
            })
        },
        doImportTranscript(text) {
            console.log("import", text);
        },
        fetchContributors(done) {
            this.axios.get(ajaxurl, {params: {action: 'podlove_transcript_get_contributors'}}).then(({data}) => {
                if (data.error) {
                    // this.lastError = data.error
                } else {
                    this.contributors = data.contributors
                    if (done) {
                        done()
                    }
                }
            })
        },
        fetchVoices() {
            this.axios.get(ajaxurl, {
                params: {
                    action: 'podlove_transcript_get_voices',
                    post_id: document.querySelector('#post_ID').value
                }
            }).then(({data}) => {
                if (data.error) {
                    // this.lastError = data.error
                } else {
                    this.voices = data.voices.map((k) => {
                        
                        let option = this.contributorsOptions.find((co) => {
                            return co.value == k.contributor_id
                        })

                        return {label: k.voice, value: k.contributor_id, option: option}
                    })
                }
            })
        }
    },

    mounted() {
        this.fetchContributors(() => {
            this.fetchVoices()
        })
    }
}
</script>

<style type="text/css">
.row {
    position: relative;
}
.row.voices {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
}
.row.voices .voice {
    display: flex;
    padding: 2px;
}
.row.voices .voice:nth-child(odd) {
    background: #F6F6F6;
}
.voice-label {
    width: 105px;
    line-height: 38px;
    padding-left: 2px;
}
.col-md-12 {
    width: 100%;
}
.col-md-8 {
    flex-grow: 3;
}
.col-md-4 {
    flex-grow: 1;
}
.transcripts-tab-wrapper {
    width: 100%;
    height:28px;
    display: block;
}
.transcripts-tab-wrapper::after {
    clear: both;
}
.transcripts-tab {
    float: left;
    border: 1px solid #ccc;
    border-bottom: none;
    margin-right: 0.5em;
    padding: 1px 10px;
    font-size: 1em;
    line-height: 24px;
    background: #f6f6f6;
}
a.transcripts-tab, a.transcripts-tab:focus {
    text-decoration: none;
    color: #555;
    outline: none;
    box-shadow: none
}
.transcripts-tab-active,
.transcripts-tab:hover{
    background: white;
}
.import form {
    border: 2px dashed #999;
    padding: 20px 20px;
    margin-bottom: 10px;
    text-align: center;
}

.import .description {
    margin-top: 10px;
    color: #666;
}
</style>
