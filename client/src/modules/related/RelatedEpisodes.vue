<template>
  <module name="relatedEpisodes" :title="__('Related episodes')">
    <PodloveListbox
      placeholder="Select episode"
      :options="state.episodeList"
      v-model="state.selectEpisodes"
      @update:model-value = "updateRelEpisodes"
      multiple
    >
    </PodloveListbox>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'

import Module from '@components/module/Module.vue'
import PodloveListbox from '@components/listbox/Listbox.vue'
import * as related from '@store/relatedEpisodes.store'

export default defineComponent({
  components: {
    Module,
    PodloveListbox,
  },
  setup() {
    return {
      state: mapState({
        episodeList: selectors.relatedEpisodes.episodeList,
        selectEpisodes: selectors.relatedEpisodes.selectEpisode,
      }),
      dispatch: injectStore().dispatch,
    }
  },
  created() {
    this.dispatch(related.init())
  },

  methods: {
    updateRelEpisodes() {
      this.dispatch(related.setSelectedEpisodes(this.state.selectEpisodes))
    }
  }
})

</script>

<style lang="postcss">
</style>
