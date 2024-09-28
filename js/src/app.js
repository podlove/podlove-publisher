import { createApp } from 'vue'

import JobsDashboard from './components/JobsDashboard.vue'
import AnalyticsDatePicker from './components/AnalyticsDatePicker.vue'
import Slacknotes from './components/Slacknotes.vue'
import Shownotes from './components/Shownotes.vue'
import ShownotesEntry from './components/ShownotesEntry.vue'
import Draggable from 'vuedraggable'

createApp(JobsDashboard).mount('#podlove-tools-dashboard')
createApp(Slacknotes).mount('#slacknotes-app')

// FIXME: draggable does not work
// FIXME: everything is displayed multiple times
const shownotesApp = createApp({})

shownotesApp.component('shownotes', Shownotes)
shownotesApp.component('shownotes-entry', ShownotesEntry)
shownotesApp.component('draggable', Draggable)
shownotesApp.mount('#podlove-shownotes-app')

import 'v2-datepicker/lib/index.css'
import V2Datepicker from 'v2-datepicker'

// FIXME: Datepicker not working yet
const analyticsApp = createApp({})
analyticsApp.use(V2Datepicker)
analyticsApp.component('analytics-date-picker', AnalyticsDatePicker)
analyticsApp.mount('#podlove-analytics-app')

// TODO: check every "app" that it works
