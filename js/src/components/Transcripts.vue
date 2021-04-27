<template>
  <div class="container">
    <div class="row">
      <div class="transcripts-tab-wrapper">
        <a
          href="#"
          class="transcripts-tab"
          :class="{'transcripts-tab-active': mode === 'transcript'}"
          @click.prevent="mode = 'transcript'"
        >Transcript</a>

        <a
          href="#"
          class="transcripts-tab"
          :class="{'transcripts-tab-active': mode === 'voices'}"
          @click.prevent="mode = 'voices'"
        >Voices</a>

        <a
          href="#"
          class="transcripts-tab"
          :class="{'transcripts-tab-active': mode === 'import'}"
          @click.prevent="mode = 'import'"
        >Import</a>

        <a
          href="#"
          class="transcripts-tab"
          :class="{'transcripts-tab-active': mode === 'export'}"
          @click.prevent="mode = 'export'"
        >Export</a>

        <a
          href="#"
          class="transcripts-tab"
          :class="{'transcripts-tab-active': mode === 'delete'}"
          @click.prevent="mode = 'delete'"
        >Delete</a>
      </div>
    </div>

    <div class="row tab-body transcript" v-show="mode == 'transcript'">
      <div v-if="transcript != null">
        <div class="ts-group col-md-12" v-for="(group, index) in transcript" :key="index">
          <div class="ts-speaker-avatar" v-if="hasVoice(group)">
            <img :src="getVoice(group).option.avatar" width="50" height="50" />
          </div>
          <div
            v-else
            class="ts-speaker-avatar"
            style="width: 50px; height: 50px; margin-top: 0px; color: rgba(183, 188, 193, 1.000)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                clip-rule="evenodd"
              />
            </svg>
          </div>

          <div class="ts-text">
            <div class="ts-speaker" v-if="hasVoice(group)">{{ getVoice(group).option.label }}</div>
            <div v-else class="ts-speaker">{{ group.voice }}</div>

            <div class="ts-content">
              <span
                class="ts-line"
                v-for="(line, index) in group.items"
                :key="index"
              >{{ line.text }}&nbsp;</span>
            </div>
          </div>
        </div>
      </div>
      <div v-else>
        <div class="empty-state">
          No transcript available yet. You need to
          <a
            href="#"
            @click.prevent="mode = 'import'"
          >import</a> one.
        </div>
      </div>
    </div>

    <div class="row tab-body voices" v-show="mode == 'voices'">
      <div v-if="voices != null && voices.length">
        <div class="voice col-md-12" v-for="(voice, index) in voices" :key="index">
          <div class="voice-label">{{ voice.label }}</div>
          <div class="voice-assignment">
            <v-select
              :options="contributorsOptions"
              label="label"
              v-model="voice.option"
              style="width: 300px"
            >
              <template v-slot:option="option">
                <img :src="option.avatar" width="16" height="16" />
                {{ option.label }}
              </template>
            </v-select>
          </div>
        </div>
      </div>
      <div v-else>
        <div class="empty-state">
          No voices available yet. You need to
          <a href="#" @click.prevent="mode = 'import'">import</a> a transcript with voice tags.
        </div>
      </div>
    </div>

    <div class="row tab-body import" v-show="mode == 'import'">
      <form>
        <div style="display: flex;justify-content: center; align-items: baseline;">
          <button
            class="button button-primary"
            @click.prevent="initImportTranscript"
          >Import Transcript</button>
          <span style="padding: 0px 15px;">or</span>
          <button class="button" @click.prevent="importTranscriptFromAsset">Get from Asset</button>
        </div>

        <input
          type="file"
          name="transcriptimport"
          id="transcriptimport"
          @change="importTranscript"
          style="display: none"
          :disabled="importing"
        />
        <div class="description" v-html="description"></div>
      </form>
    </div>

    <div class="row tab-body export" v-show="mode == 'export'">
      <a
        class="button button-secondary"
        :href="webvttDownloadHref"
        download="transcript.webvtt"
      >Export webvtt</a>
      <a
        class="button button-secondary"
        :href="jsonDownloadHref"
        download="transcript.json"
      >Export json (flat)</a>
      <a
        class="button button-secondary"
        :href="jsonGroupedDownloadHref"
        download="transcript.json"
      >Export json (grouped)</a>
      <a
        class="button button-secondary"
        :href="xmlDownloadHref"
        download="transcript.xml"
      >Export xml</a>
    </div>

    <div class="row tab-body delete" v-show="mode == 'delete'">
      <button class="button button-secondary" @click.prevent="deleteTranscript">Delete webvtt</button>
    </div>
  </div>
</template>

<script type="text/javascript">
export default {
  data() {
    return {
      mode: "import",
      importing: false,
      lastError: "",
      voices: null,
      contributors: null,
      transcript: null,
    };
  },

  watch: {
    voiceData: function (val, oldVal) {
      if (oldVal == null) {
        return;
      }

      jQuery
        .ajax({
          url:
            podlove_vue.rest_url +
            "podlove/v1/transcripts/" +
            podlove_vue.post_id +
            "/voices",
          method: "POST",
          dataType: "json",
          data: {
            transcript_voice: val,
          },
        })
        .done((result) => {
          // console.log("saved transcript voices", result);
        })
        .fail(({ responseJSON }) => {
          console.error(
            "error saving transcript voices:",
            responseJSON.message
          );
        });

      // TODO: clearing/deleting assignments
    },
  },

  computed: {
    description: function () {
      if (this.importing) {
        return "importing ...";
      } else if (this.lastError) {
        return this.lastError;
      } else {
        return "Accepts: WebVTT";
      }
    },
    voiceData: function () {
      if (!this.voices) {
        return null;
      }

      return this.voices.reduce((agg, voice) => {
        agg[voice.label] = voice.option ? voice.option.value : voice.value;
        return agg;
      }, new Object());
    },
    contributorsOptions: function () {
      let options = this.contributors.map((c) => {
        return { label: c.name, value: c.id, avatar: c.avatar };
      });

      options.unshift({
          label: "None (hide voice)",
          value: 0,
          avatar: "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10'><path d='M6.895 5l2.843-2.843a.894.894 0 000-1.264L9.107.262a.894.894 0 00-1.264 0L5 3.105 2.157.262a.894.894 0 00-1.264 0L.262.893a.894.894 0 000 1.264L3.105 5 .262 7.843a.894.894 0 000 1.264l.631.631a.894.894 0 001.264 0L5 6.895l2.843 2.843a.894.894 0 001.264 0l.631-.631a.894.894 0 000-1.264L6.895 5z'/></svg>"
      })

      return options;
    },
    webvttDownloadHref: function () {
      const post_id = document.querySelector("#post_ID").value;
      const host = window.location.hostname;

      return "//" + host + "?p=" + post_id + "&podlove_transcript=webvtt";
    },
    jsonDownloadHref: function () {
      const post_id = document.querySelector("#post_ID").value;
      const host = window.location.hostname;

      return "//" + host + "?p=" + post_id + "&podlove_transcript=json";
    },
    xmlDownloadHref: function () {
      const post_id = document.querySelector("#post_ID").value;
      const host = window.location.hostname;

      return "//" + host + "?p=" + post_id + "&podlove_transcript=xml";
    },
    jsonGroupedDownloadHref: function () {
      const post_id = document.querySelector("#post_ID").value;
      const host = window.location.hostname;
      const port = parseInt(window.location.port, 10);
      let fullhost = host;

      if (port && port > 0) {
        fullhost = fullhost + ":" + port;
      }

      return (
        "//" + fullhost + "?p=" + post_id + "&podlove_transcript=json_grouped"
      );
    },
  },

  methods: {
    initImportTranscript() {
      const fileInput = document.getElementById("transcriptimport");
      fileInput.click();
    },
    deleteTranscript() {
      if (window.confirm("Delete transcript from this episode?")) {
        this.axios
          .get(ajaxurl, {
            params: {
              action: "podlove_transcript_delete",
              post_id: document.querySelector("#post_ID").value,
            },
          })
          .then(({ data }) => {
            if (data.error) {
              this.lastError = data.error;
            } else {
              this.refetchAll();
              window.setTimeout(() => {
                this.mode = "transcript";
              }, 1000);
            }
          })
          .catch((error) => {});
      }
    },
    importTranscript() {
      const fileInput = document.getElementById("transcriptimport");
      const file = fileInput.files[0];

      this.importing = true;

      let form = new FormData();
      form.append("transcript", file);
      form.append("action", "podlove_transcript_import");
      form.append("post_id", document.querySelector("#post_ID").value);

      this.axios
        .post(ajaxurl, form, {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        })
        .then(({ data }) => {
          if (data.error) {
            this.lastError = data.error;
          } else {
            this.refetchAll();
          }
          this.importing = false;
          fileInput.parentElement.reset();
        })
        .catch((error) => {
          this.importing = false;
          fileInput.parentElement.reset();
        });
    },
    importTranscriptFromAsset() {
      console.log("importTranscriptFromAsset");

      this.importing = true;

      this.axios
        .get(ajaxurl, {
          params: {
            action: "podlove_transcript_asset_import",
            post_id: document.querySelector("#post_ID").value,
          },
        })
        .then(({ data }) => {
          if (data.error) {
            this.lastError = data.error;
          } else {
            this.refetchAll();
          }
          this.importing = false;
        })
        .catch((error) => {
          this.importing = false;
        });
    },
    fetchContributors(done) {
      this.axios
        .get(ajaxurl, {
          params: { action: "podlove_transcript_get_contributors" },
        })
        .then(({ data }) => {
          if (data.error) {
            // this.lastError = data.error
          } else {
            this.contributors = data.contributors;
            if (done) {
              done();
            }
          }
        });
    },
    fetchVoices(done) {
      this.axios
        .get(ajaxurl, {
          params: {
            action: "podlove_transcript_get_voices",
            post_id: document.querySelector("#post_ID").value,
          },
        })
        .then(({ data }) => {
          if (data.error) {
            // this.lastError = data.error
          } else {
            this.voices = data.voices.map((k) => {
              let option = this.contributorsOptions.find((co) => {
                return co.value == k.contributor_id;
              });

              return {
                label: k.voice,
                value: k.contributor_id,
                option: option,
              };
            });
            if (done) {
              done();
            }
          }
        });
    },
    getVoice(group) {
      let id = this.voiceData[group.voice];
      return this.voices.find((v) => (v.option ? v.option.value == id : false));
    },
    hasVoice(group) {
      return group && group.voice && this.voiceData[group.voice];
    },
    fetchTranscript(done) {
      this.axios.get(this.jsonGroupedDownloadHref).then(({ data }) => {
        this.transcript = data;
        if (done) {
          done();
        }
      });
    },
    refetchAll() {
      this.fetchContributors(() => {
        this.fetchVoices(() => {
          this.fetchTranscript(() => {
            if (this.transcript && this.voices) {
              this.mode = "transcript";
            } else if (!this.voices) {
              this.mode = "voices";
            }
          });
        });
      });
    },
  },

  mounted() {
    this.refetchAll();
  },
};
</script>

<style type="text/css">
.row {
  position: relative;
}
.tab-body {
  border: 1px solid rgb(204, 204, 204);
  min-height: 20px;
  padding: 10px;
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
  background: #f6f6f6;
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
  height: 28px;
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
a.transcripts-tab,
a.transcripts-tab:focus {
  text-decoration: none;
  color: #555;
  outline: none;
  box-shadow: none;
}
.transcripts-tab-active,
.transcripts-tab:hover {
  background: white;
  border-bottom: 1px solid white;
  position: relative;
  top: 1px;
  z-index: 10;
}
.import form {
  border: 2px dashed #999;
  padding: 20px 20px;
  text-align: center;
}

.import .description {
  margin-top: 10px;
  color: #666;
}

.empty-state {
  padding: 10px 0px;
}

.row.transcript {
  max-height: 400px;
  overflow-y: auto;
}

.ts-group {
  clear: both;
  margin-top: 15px;
}
.ts-group:first-child {
  margin-top: 0px;
}
.ts-speaker-avatar {
  margin-top: 5px;
  float: left;
}
.ts-speaker-avatar img {
  border-radius: 10%;
}
.ts-speaker {
  font-weight: bold;
  /* font-size: 90%; */
}
.ts-items {
  margin-left: 20px;
}
.ts-time {
  font-size: small;
  color: #999;
}
.ts-text {
  margin-left: 60px;
}
.ts-line:hover {
  background-color: #f9f9f9;
}
</style>
