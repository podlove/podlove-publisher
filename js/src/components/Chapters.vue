<template>
    <div class="container">
        <div class="row">

            <div :class="{'col-md-8': activeChapter, 'col-md-12': !activeChapter}">
                <chapter 
                    v-for="chapter in sortedChapters"
                    @activate="activateChapter(chapter)" 
                    :active="isActive(chapter)"
                    :start="chapter.start" 
                    :title="chapter.title"
                    :duration="durationForChapter(chapter)"
                    >
                </chapter>
            </div>

            <div class="col-md-4 chapter-edit" v-if="activeChapter">
                <div class="form-group">
                    <label for="chapter_title">Title</label>
                    <input type="text" v-model="activeChapter.title" class="form-control" id="chapter_title">
                </div>
                <div class="form-group">
                    <label for="chapter_start">Start Time</label>
                    
                        <label class="form-check-inline">
                            <input class="form-check-input" type="radio" name="timeformat" value="ms" autocomplete="off" v-model="timeformat"> milliseconds
                        </label>
                        <label class="form-check-inline">
                            <input class="form-check-input" type="radio" name="timeformat" value="hr" autocomplete="off" v-model="timeformat"> human readable
                        </label>

                    <input type="text" :value="formatTime(activeChapter.start.totalMs)" v-on:input="activeChapter.start.totalMs = unformatTime($event.target.value)" class="form-control" id="chapter_start">
                </div>

                <div class="form-group">
                    <button class="button button-secondary delete-chapter" @click.prevent="deleteActiveChapter">Delete Chapter</button>
                </div>
            </div>

        </div>

        <hr>

        <div class="row">
                <button class="button button-secondary" @click.prevent="addChapter">Add Chapter</button>

                &nbsp;

                <button class="button button-secondary" @click.prevent="importChapters">Import Chapter File</button>

                &nbsp;

                <input type="file" name="chapterimport" id="chapterimport"> 

                <div style="float: right">
                    <small>
                        Export: <a :href="mp4chapsDownloadHref" download="chapters.txt">mp4chaps</a> 
                        <a :href="pscDownloadHref" download="chapters.psc">psc</a>
                    </small>
                </div>
        </div>

        <!-- invisible textarea used for persisting -->
        <textarea name="_podlove_meta[chapters]" v-model="chaptersAsMp4chaps" style="display: none"></textarea>

        <!-- 
        <hr>

        <div class="row export-row">
            <div class="col-md-12">
                <h2>MP4Chaps <small><a :href="mp4chapsDownloadHref" download="chapters.txt">download</a></small></h2>
                <textarea class="form-control" v-bind:rows="chapters.length" v-model="chaptersAsMp4chaps" readonly></textarea>
            </div>
        </div>

        <hr>

        <div class="row export-row">
            <div class="col-md-12">
                <h2>Podlove Simple Chapters <small><a :href="pscDownloadHref" download="chapters.psc">download</a></small></h2> 
                <textarea class="form-control" v-bind:rows="chapters.length + 2" v-model="chaptersAsPSC" readonly></textarea>
            </div>
        </div>
         -->

    </div>
</template>

<script>
// import MP4Chaps from 'podcast-chapter-parser-mp4chaps';
const MP4Chaps = require('podcast-chapter-parser-mp4chaps');
const psc = require('podcast-chapter-parser-psc').parser(window.DOMParser);
const npt = require('normalplaytime');
import Timestamp from "../timestamp"
import guid from "../guid"

class Chapter {
  constructor(title, start) {
    this.id = guid();
    this.title = title;
    this.start = start;
  }
}

export default {

    data() {
        // let c1 = new Chapter("Intro", new Timestamp(0));
        return {
            chapters: [],
            activeChapter: null,
            timeformat: 'hr'
        }
    },

    methods: {
        activateChapter (chapter) {
            this.activeChapter = chapter;

            // there is probably a better way then random timeout
            window.setTimeout(() => {
                let titleInput = document.getElementById('chapter_title');

                if (!!titleInput) {
                    titleInput.focus();
                }
            }, 100);

        },
        isActive (chapter) {
            return this.activeChapter && chapter.id == this.activeChapter.id;
        },
        addChapter () {
            let newChapter = new Chapter("", new Timestamp(0));
            this.chapters.push(newChapter);
            this.activateChapter(newChapter);
        },
        deleteActiveChapter() {

            let index = this.chapters.indexOf(this.activeChapter);

            if (index < 0) {
                return;
            }

            this.chapters.splice(index, 1);

            // activate the nearest item (next, or previous if there is no next)
            if (this.chapters[index]) {
                this.activateChapter(this.chapters[index]);
            } else if (this.chapters[index - 1]) {
                this.activateChapter(this.chapters[index - 1]);
            } else {
                this.activateChapter(null);
            }
            
        },
        durationForChapter(chapter) {
            const curIndex = this.chapters.indexOf(chapter);
            // const prevIndex = curIndex - 1;
            const nextIndex = curIndex + 1;
            const nextChapter = this.chapters[nextIndex];

            if (!nextChapter) {

                // check if total duration is known
                const totalDurationEl = document.getElementById('_podlove_meta_duration');
                if (totalDurationEl && totalDurationEl.value) {
                    const totalDuration = npt.parse(totalDurationEl.value);
                    if (totalDuration) {
                        return totalDuration - chapter.start.totalMs;
                    }
                }

                return -1;

            } else {
                return nextChapter.start.totalMs - chapter.start.totalMs;
            }

        },
        importChapters() {
            const fileInput = document.getElementById("chapterimport");
            const file = fileInput.files[0];

            const reader = new FileReader();
            reader.onload = (e) => {
                this.doImportChapters(e.target.result);
            };
            reader.readAsText(file);
        },
        doImportChapters(text) {
            var chapters = MP4Chaps.parse(text);
            
            if (!chapters || !chapters.length) {
                chapters = psc.parse(text);
            }

            if (!chapters || !chapters.length) {
                return;
            }

            this.chapters = chapters.reduce(function(agg, chapter) {

                agg.push(new Chapter(chapter.title, new Timestamp(chapter.start)));

                return agg;
            }, []);
        },
        formatTime(t) {
            const ms = this.msFromWhateverTimestring(t);

            if (this.timeformat == 'hr') {
                const timestamp = new Timestamp(ms);
                return timestamp.pretty;
            } else {
                return ms;
            }
        },
        unformatTime(t) {
            return this.msFromWhateverTimestring(t);
        },
        msFromWhateverTimestring(t) {
            let ms = 0;

            if (t == parseInt(t, 10)) {
                ms = parseInt(t, 10);
            } else {
                ms = npt.parse(t);
            }

            return ms;            
        }
    },

    computed: {
        sortedChapters () {
            return this.chapters.sort((a, b) => a.start.totalMs - b.start.totalMs);
        },
        chaptersAsMp4chaps () {
            return this.sortedChapters.reduce((agg, chapter) => {

                agg.push(chapter.start.pretty + " " + chapter.title);

                return agg;
            }, []).join("\n") + "\n";
        },
        chaptersAsPSC () {
            let serializer = new XMLSerializer();

            let psc = "<psc:chapters version=\"1.2\" xmlns:psc=\"http://podlove.org/simple-chapters\"/>";
            let parser = new DOMParser();
            let xmlDoc = parser.parseFromString(psc, "text/xml");

            // need both tries for Chrome/Firefox compatibility            
            let pscDoc = xmlDoc.getElementsByTagName("chapters");
            if (!pscDoc.length) {
                pscDoc = xmlDoc.getElementsByTagName("psc:chapters");
            }
            pscDoc = pscDoc[0];

            this.sortedChapters.forEach((chapter) => {
                let node = xmlDoc.createElement("psc:chapter");
                node.setAttribute("title", chapter.title);
                node.setAttribute("start", chapter.start.pretty);
                pscDoc.appendChild(node);
            });

            let serialized = serializer.serializeToString(xmlDoc);

            // poor man's formatting
            let formatted = serialized
              .replace(/\<psc:chapter\s/gi, "\n    <psc:chapter ")
              .replace("</psc:chapters>", "\n</psc:chapters>")

            return formatted;
        },
        mp4chapsDownloadHref() {
            var blob = new Blob([this.chaptersAsMp4chaps], {type: 'text/plain'});
            return window.URL.createObjectURL(blob);
        },
        pscDownloadHref() {
            var blob = new Blob([this.chaptersAsPSC], {type: 'application/xml'});
            return window.URL.createObjectURL(blob);
        }
    },

    mounted() {
        let chapters = JSON.parse(document.getElementById('podlove-chapters-app-data').innerHTML);
        console.log("inital chapters", chapters);
        this.chapters = chapters.map((c) => {
            return new Chapter(c.title, new Timestamp(this.msFromWhateverTimestring(c.start)));
        });
    }
}
</script>

<style type="text/css">
.row { display: flex; }
.col-md-12 { width: 100% }
.col-md-8 {
    width: 66%;
}
.col-md-4 {
    width: 33%;
}
.chapter {
    padding: 2px;
    /*border: 1px solid white;*/
    /*border-width: 0px;*/
    display: flex;
    cursor: pointer;
}
.chapter:hover,
.chapter.active {
    background: #EEE;
}
.title {
    flex-grow: 4;
    text-align: left;
}
.duration {
    flex-grow: 1;
    text-align: right;
    cursor: text;
    font-family: monospace;
}

.time {
    /*flex-grow: 2;*/
    width: 105px;
    text-align: left;
    cursor: text;
    font-family: monospace;
}
.chapter-edit {
    padding-left: 20px
}
.chapter-edit input[type=text] {
    width: 100%;
}
label[for=chapter_title],
label[for=chapter_start] {
    display: block;
    font-weight: bold;
}
label[for=chapter_start],
button.button.delete-chapter {
    margin-top: 10px;
}
</style>
