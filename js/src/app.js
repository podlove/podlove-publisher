import Vue from 'vue'

import Chapters from './components/Chapters'
import Chapter from './components/Chapter'
import ChapterForm from './components/ChapterForm'
import JobsDashboard from './components/JobsDashboard'
import Slacknotes from './components/Slacknotes'

Vue.component('chapters', Chapters);
Vue.component('chapter', Chapter);
Vue.component('chapter-form', ChapterForm);
Vue.component('jobs-dashboard', JobsDashboard);
Vue.component('jobs-dashboard', JobsDashboard);
Vue.component('slacknotes', Slacknotes);

import 'v2-datepicker/lib/index.css'
import V2Datepicker from 'v2-datepicker'

Vue.use(V2Datepicker)

if (document.getElementById('podlove-chapters-app')) {
    window.chaptersApp = new Vue({
        el: '#podlove-chapters-app'
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
