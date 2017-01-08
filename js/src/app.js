window.Vue = require('vue');

Vue.component('chapters', require('./components/Chapters.vue'));
Vue.component('chapter', require('./components/Chapter.vue'));

const app = new Vue({
    el: '#podlove-chapters-app'
});
