<template>
    <div class="container">

        <div class="row import" v-show="mode == 'import'">

            <button class="exit" @click.prevent="mode = 'chapters'">✕ Close</button>

            <p>
                <strong>Import</strong>
            </p>

            <p>
                <input type="file" name="chapterimport" id="chapterimport"> 

                <button class="button button-primary" @click.prevent="importChapters">Import Chapters</button>
            </p>
        </div>    
        
        <div class="row export" v-show="mode == 'export'">

            <button class="exit" @click.prevent="mode = 'chapters'">✕ Close</button>

            <p>
                <strong>Export</strong>
            </p>
            <p>
                <a class="button button-secondary" :href="mp4chapsDownloadHref" download="chapters.txt">mp4chaps</a> 
                <a class="button button-secondary" :href="pscDownloadHref" download="chapters.psc">psc</a>
            </p>
        </div>

        <div class="row chapters" v-show="mode == 'chapters'">

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

            <div style="height: 1px; width: 20px;"></div>

            <chapter-form 
                :chapter="activeChapter" 
                @deleteChapter="onDeleteChapter"
                @unselectChapter="activeChapter = null"
                @selectPrevChapter="onSelectPrevChapter"
                @selectNextChapter="onSelectNextChapter"
                ></chapter-form>

        </div>

        <hr>

        <div class="row">

            <div style="float: left" v-show="mode == 'chapters'">

                <button class="button button-secondary" @click.prevent="addChapter">Add Chapter</button>
                
            </div>

            <div style="float: right">
                
                <button class="button button-secondary" @click.prevent="mode = 'import'">Import</button>
                <button class="button button-secondary" @click.prevent="mode = 'export'">Export</button>

            </div>

            <div style="clear: both"></div>

        </div>

        <!-- invisible textarea used for persisting -->
        <textarea name="_podlove_meta[chapters]" id="_podlove_meta_chapters" v-model="chaptersAsMp4chaps" style="display: none"></textarea>

    </div>
</template>

<script>
// import MP4Chaps from 'podcast-chapter-parser-mp4chaps';
const MP4Chaps = require('podcast-chapter-parser-mp4chaps');
const psc = require('podcast-chapter-parser-psc').parser(window.DOMParser);
const npt = require('normalplaytime');
import Timestamp from '../timestamp'
import guid from '../guid'
import DurationErrors from '../duration_errors'

class Chapter {
  constructor(title, start) {
    this.id = guid();
    this.title = title;
    this.start = start;
  }
}

export default {

    data() {
        return {
            chapters: [],
            activeChapter: null,
            timeformat: 'hr',
            mode: 'chapters',
            episodeDuration: 0
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
        onDeleteChapter(chapter) {
            let index = this.chapters.indexOf(chapter);

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
        onSelectPrevChapter() {
            let index = this.chapters.indexOf(this.activeChapter) - 1;

            if (this.chapters[index]) {
                this.activateChapter(this.chapters[index]);
            }

        },
        onSelectNextChapter() {
            let index = this.chapters.indexOf(this.activeChapter) + 1;

            if (this.chapters[index]) {
                this.activateChapter(this.chapters[index]);
            }
        },
        durationForChapter(chapter) {
            const curIndex = this.chapters.indexOf(chapter);
            // const prevIndex = curIndex - 1;
            const nextIndex = curIndex + 1;
            const nextChapter = this.chapters[nextIndex];

            if (!nextChapter) {

                if (!this.episodeDuration)
                    return DurationErrors.TOTAL_INVALID;

                const duration = this.episodeDuration - chapter.start.totalMs;

                if (duration < 0)
                    return DurationErrors.LONGER_THAN_TOTAL;                            

                return duration;

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
            this.mode = 'chapters';
            this.activeChapter = null;
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
        readEpisodeDuration() {
            const duration = document.getElementById('_podlove_meta_duration');
            if (duration && duration.value) {
                this.episodeDuration = npt.parse(duration.value);
            }
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
        try {
            let chapters = JSON.parse(document.getElementById('podlove-chapters-app-data').innerHTML);
            this.chapters = chapters.map((c) => {
                return new Chapter(c.title, Timestamp.fromString(c.start));
            });
        } catch (e) {

        }

        // monitor changes of episode duration
        this.readEpisodeDuration();
        
        const episodeDuration = document.getElementById('_podlove_meta_duration');
        const refresh = (e) => { this.readEpisodeDuration(); };

        episodeDuration.addEventListener('change', refresh);
        episodeDuration.addEventListener('keyup', refresh);
    }
}

</script>

<style type="text/css">
.row {
    position: relative;
}
.exit {
    position: absolute;
    top: 0;
    right: 0;
    background: none;
    border-width: 0;
    font-weight: bold;
    cursor: pointer;
}
.row.chapters {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
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
.chapter-edit {
    /*padding-left: 20px*/
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
