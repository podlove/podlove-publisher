import Vue from 'vue'

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

if (document.getElementById('podlove-tools-dashboard')) {
    const toolsDashboard = new Vue({
        el: '#podlove-tools-dashboard'
    });
}
