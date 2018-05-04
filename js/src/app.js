import Vue from 'vue'

import axios from 'axios'
import VueAxios from 'vue-axios'
import vSelect from 'vue-select'

Vue.use(VueAxios, axios)

Vue.component('v-select', vSelect)

// chapters

import Chapters from './components/Chapters'
import Chapter from './components/Chapter'
import ChapterForm from './components/ChapterForm'
import JobsDashboard from './components/JobsDashboard'

Vue.component('chapters', Chapters);
Vue.component('chapter', Chapter);
Vue.component('chapter-form', ChapterForm);
Vue.component('jobs-dashboard', JobsDashboard);

if (document.getElementById('podlove-chapters-app')) {
    window.chaptersApp = new Vue({
        el: '#podlove-chapters-app'
    });
}

// transcripts

Vue.component('transcripts', require('./components/Transcripts.vue'));

if (document.getElementById('podlove-transcripts-app')) {
    window.transcriptsApp = new Vue({
        el: '#podlove-transcripts-app'
    });
}

// job dashboard

if (document.getElementById('podlove-tools-dashboard')) {
    const toolsDashboard = new Vue({
        el: '#podlove-tools-dashboard'
    });
}
