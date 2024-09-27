import { createApp } from 'vue'

import JobsDashboard from './components/JobsDashboard.vue'
import AnalyticsDatePicker from './components/AnalyticsDatePicker.vue'
import Slacknotes from './components/Slacknotes.vue'
import Shownotes from './components/Shownotes.vue'
import ShownotesEntry from './components/ShownotesEntry.vue'
import Draggable from 'vuedraggable'

createApp(JobsDashboard).mount('#podlove-tools-dashboard')
createApp(Slacknotes).mount('#slacknotes-app')
createApp(Shownotes).mount('#podlove-shownotes-app')

import 'v2-datepicker/lib/index.css'
import V2Datepicker from 'v2-datepicker'

const analyticsApp = createApp(AnalyticsDatePicker)
analyticsApp.use(V2Datepicker)
analyticsApp.mount('#podlove-analytics-app')

// vue.component('shownotes-entry', ShownotesEntry)
// vue.component('draggable', Draggable)

// TODO: check every "app" that it works
