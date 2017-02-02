window.Vue = require('vue');

Vue.component('chapters', require('./components/Chapters.vue'));
Vue.component('chapter', require('./components/Chapter.vue'));
Vue.component('chapter-form', require('./components/ChapterForm.vue'));

if (document.getElementById('podlove-chapters-app')) {
    const chapters = new Vue({
        el: '#podlove-chapters-app'
    });
}

Vue.component('jobs-dashboard', require('./components/JobsDashboard.vue'));

if (document.getElementById('podlove-tools-dashboard')) {
    const toolsDashboard = new Vue({
        el: '#podlove-tools-dashboard'
    });
}
