<template>
  <div v-if="contributors.length > 0">
    <div class="m-3">
      
      <podlove-button variant="primary">
        <plus-sm-icon class="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true" /> Add Contributor
      </podlove-button>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'
import { selectors } from '@store'
import { PlusSmIcon, BookmarkAltIcon } from '@heroicons/vue/outline'

import PodloveButton from '@components/button/Button.vue'

interface Contributor {
  id: string;
  avatar: string;
  count: string;
  department: string;
  gender: string;
  jobtitle: string;
  mail: string;
  name: string;
  nickname: string;
  organisation: string;
  slug: string;
}

export default defineComponent({
  components: { PodloveButton, PlusSmIcon, BookmarkAltIcon },

  setup() {
    return {
      state: mapState({
        contributors: selectors.contributors.list,
        chapters: selectors.chapters.list,
        selected: selectors.chapters.selected,
        selectedIndex: selectors.chapters.selectedIndex,
        episodeDuration: selectors.episode.duration,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  computed: {
    contributors(): Contributor[] {
      return this.state.contributors
    },
  },

  methods: {},
})
</script>

<style></style>
