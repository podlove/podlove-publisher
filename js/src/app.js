import Vue from 'vue'
import axios from 'axios'
import VueAxios from 'vue-axios'
import vSelect from 'vue-select'

Vue.use(VueAxios, axios)

Vue.component('v-select', vSelect)

// chapters

Vue.component('chapters', require('./components/Chapters.vue'));
Vue.component('chapter', require('./components/Chapter.vue'));
Vue.component('chapter-form', require('./components/ChapterForm.vue'));

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

Vue.component('jobs-dashboard', require('./components/JobsDashboard.vue'));

if (document.getElementById('podlove-tools-dashboard')) {
    const toolsDashboard = new Vue({
        el: '#podlove-tools-dashboard'
    });
}
