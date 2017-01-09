window.Vue = require('vue');

Vue.component('chapters', require('./components/Chapters.vue'));
Vue.component('chapter', require('./components/Chapter.vue'));
Vue.component('chapter-form', require('./components/ChapterForm.vue'));

const app = new Vue({
    el: '#podlove-chapters-app'
});
