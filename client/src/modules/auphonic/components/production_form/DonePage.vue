<template>
  <div class="rounded-md bg-green-50 p-4">
    <div class="flex">
      <div class="flex-shrink-0">
        <ClipboardCheckIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-green-800">Done</h3>
        <div class="mt-2 text-sm text-green-700">
          <p>
            <a
              :href="production.status_page"
              target="_blank"
              class="underline inline-flex items-center"
              >View Results <ExternalLinkIcon class="ml-0.5 mr-1 h-4 w-4" aria-hidden="true"
            /></a>
            on the Auphonic status page.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import { Production } from '@store/auphonic.store'

import { ClipboardCheckIcon } from '@heroicons/vue/outline'

export default defineComponent({
  components: {
    ClipboardCheckIcon,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
  },
})
</script>
