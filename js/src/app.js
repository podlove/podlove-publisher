import Vue from 'vue'

import axios from 'axios'
import VueAxios from 'vue-axios'
import vSelect from 'vue-select'
import 'vue-select/dist/vue-select.css';

Vue.use(VueAxios, axios)

Vue.component('v-select', vSelect)

// chapters

import Chapters from './components/Chapters'
import Chapter from './components/Chapter'
import ChapterForm from './components/ChapterForm'
import JobsDashboard from './components/JobsDashboard'
import AnalyticsDatePicker from './components/AnalyticsDatePicker'
import Slacknotes from './components/Slacknotes'
import Shownotes from './components/Shownotes'
import ShownotesEntry from './components/ShownotesEntry'
import Transcripts from './components/Transcripts'
import Soundbite from './components/Soundbite'
import Draggable from 'vuedraggable'

Vue.component('chapters', Chapters);
Vue.component('chapter', Chapter);
Vue.component('chapter-form', ChapterForm);
Vue.component('jobs-dashboard', JobsDashboard);
Vue.component('analytics-date-picker', AnalyticsDatePicker);
Vue.component('jobs-dashboard', JobsDashboard);
Vue.component('slacknotes', Slacknotes);
Vue.component('shownotes', Shownotes);
Vue.component('shownotes-entry', ShownotesEntry);
Vue.component('transcripts', Transcripts);
Vue.component('soundbite', Soundbite);
Vue.component('draggable', Draggable);

import 'v2-datepicker/lib/index.css'
import V2Datepicker from 'v2-datepicker'

Vue.use(V2Datepicker)

if (document.getElementById('podlove-chapters-app')) {
    window.chaptersApp = new Vue({
        el: '#podlove-chapters-app'
    });
}

if (document.getElementById('podlove-transcripts-app')) {
    window.transcriptsApp = new Vue({
        el: '#podlove-transcripts-app'
    });
}

if (document.getElementById('podlove-tools-dashboard')) {
    const toolsDashboard = new Vue({
        el: '#podlove-tools-dashboard'
    });
}

if (document.getElementById('slacknotes-app')) {
    window.slacknotes = new Vue({
        el: '#slacknotes-app'
    });
}

if (document.getElementById('podlove-shownotes-app')) {
    window.shownotes = new Vue({
        el: '#podlove-shownotes-app'
    });
}

if (document.getElementById('podlove-analytics-app')) {

    window.analyticsApp = new Vue({
        el: '#podlove-analytics-app'
    });
}

if (document.getElementById('podlove-soundbite-app')) {
    window.transcriptsApp = new Vue({
        el: '#podlove-soundbite-app'
    });
}
