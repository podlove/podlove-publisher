import Vue from 'vue'

import axios from 'axios'
import VueAxios from 'vue-axios'

Vue.use(VueAxios, axios)

import JobsDashboard from './components/JobsDashboard'
import AnalyticsDatePicker from './components/AnalyticsDatePicker'
import Slacknotes from './components/Slacknotes'
import Shownotes from './components/Shownotes'
import ShownotesEntry from './components/ShownotesEntry'
import Draggable from 'vuedraggable'

Vue.component('analytics-date-picker', AnalyticsDatePicker)
Vue.component('jobs-dashboard', JobsDashboard)
Vue.component('slacknotes', Slacknotes)
Vue.component('shownotes', Shownotes)
Vue.component('shownotes-entry', ShownotesEntry)
Vue.component('draggable', Draggable)

import 'v2-datepicker/lib/index.css'
import V2Datepicker from 'v2-datepicker'

Vue.use(V2Datepicker)

if (document.getElementById('podlove-tools-dashboard')) {
  const toolsDashboard = new Vue({
    el: '#podlove-tools-dashboard',
  })
}

if (document.getElementById('slacknotes-app')) {
  window.slacknotes = new Vue({
    el: '#slacknotes-app',
  })
}

if (document.getElementById('podlove-shownotes-app')) {
  window.shownotes = new Vue({
    el: '#podlove-shownotes-app',
  })
}

if (document.getElementById('podlove-analytics-app')) {
  window.analyticsApp = new Vue({
    el: '#podlove-analytics-app',
  })
}
