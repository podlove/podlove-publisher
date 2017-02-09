<template>
    <div class="container">

        <div class="row">

            <div class="chapters-tab-wrapper">
                
                <a href="#" 
                   class="chapters-tab" 
                   :class="{'chapters-tab-active': mode === 'chapters'}" 
                   @click.prevent="mode = 'chapters'">
                   Chapters
                </a>

                <a href="#" 
                   class="chapters-tab" 
                   :class="{'chapters-tab-active': mode === 'import'}" 
                   @click.prevent="mode = 'import'">
                   Import
                </a>

                <a href="#" 
                   class="chapters-tab" 
                   :class="{'chapters-tab-active': mode === 'export'}" 
                   @click.prevent="mode = 'export'">
                   Export
                </a>

            </div>

        </div>

        <div class="row import" v-show="mode == 'import'">
            <p>
                <form>
                    <input type="file" name="chapterimport" id="chapterimport"> 
                    <div class="description">Accepts: <a href="https://podlove.org/simple-chapters/" target="_blank">Podlove Simple Chapters</a> (<code>.psc</code>) and MP4Chaps (<code>.txt</code>)</div>
                </form>

                <button class="button button-primary" @click.prevent="importChapters">Import Chapters</button>
            </p>
        </div>    
        
        <div class="row export" v-show="mode == 'export'">
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
                    :url="chapter.url"
                    :duration="durationForChapter(chapter)"
                    >
                </chapter>

                <div class="chapter" style="background: white">
                    <button class="button button-secondary" @click.prevent="addChapter">+ Add Chapter</button>
                </div>
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

        <!-- invisible textarea used for persisting -->
        <textarea name="_podlove_meta[chapters]" id="_podlove_meta_chapters" v-model="chaptersAsMp4chaps" style="display: none"></textarea>

    </div>
</template>

<script>
// import MP4Chaps from 'podcast-chapter-parser-mp4chaps';
const MP4Chaps = require('podcast-chapter-parser-mp4chaps');
const psc = require('podcast-chapter-parser-psc').parser(window.DOMParser);
const npt = require('normalplaytime');
import Timestamp from '../lib/timestamp'
import guid from '../lib/guid'
import DurationErrors from '../lib/duration_errors'

class Chapter {
  constructor(title, start, url) {
    this.id = guid();
    this.title = title;
    this.start = start;
    this.url = url;
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

            // reset import element
            fileInput.parentElement.reset();
        },
        doImportChapters(text) {
            let chapters;

            try {
                chapters = MP4Chaps.parse(text);
            } catch (e) {
                chapters = null;
            }
            
            if (!chapters || !chapters.length) {

                try {
                    chapters = psc.parse(text);
                } catch (e) {
                    chapters = null;
                }
                
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
                var line = chapter.start.pretty + " " + chapter.title;

                if (chapter.url) {
                    line = line + " <" + chapter.url + ">";
                }

                agg.push(line);

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

                if (chapter.url) {
                    node.setAttribute("href", chapter.url);
                }

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
                return new Chapter(c.title, Timestamp.fromString(c.start), c.href);
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
label[for=chapter_url],
label[for=chapter_start] {
    display: block;
    font-weight: bold;
}
label[for=chapter_start],
label[for=chapter_url],
button.button.delete-chapter {
    margin-top: 10px;
}

label[for=chapter_url] small {
    font-weight: normal;
}

.chapters-tab-wrapper {
    width: 100%;
    height:28px;
    display: block;
}
.chapters-tab-wrapper::after {
    clear: both;
}
.chapters-tab {
    float: left;
    border: 1px solid #ccc;
    border-bottom: none;
    margin-right: 0.5em;
    padding: 1px 10px;
    font-size: 1em;
    line-height: 24px;
    background: #f6f6f6;
}
a.chapters-tab {
    text-decoration: none;
    color: #555;
}
.chapters-tab-active,
.chapters-tab:hover{
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
