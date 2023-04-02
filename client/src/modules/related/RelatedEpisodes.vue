<template>
  <module name="relatedEpisodes" :title="__('Related episodes')">
    <PodloveListbox
      placeholder="Select episode"
      :options="fullEpisodeList"
      :selectValues="state.selectEpisodes"
      @update = "updateRelEpisodes($event)"
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
import PodloveListbox from '@components/combobox/Combobox.vue'
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
  computed: {
    fullEpisodeList() {
      if (this.state.selectEpisodes.length == 0) {
        const selectAllEpisodes = { id: 0, title: "Select all episodes"}
        return [selectAllEpisodes, ...this.state.episodeList]
      }
      else {
        const selectAllEpisodes = { id: -1, title: "Deselect all episodes"}
        return [selectAllEpisodes, ...this.state.episodeList]
      }
    },
  },
  methods: {
    updateRelEpisodes(newSelectedItems: Array<Number>) {
      if (newSelectedItems.includes(0)) {
        // Select all
        this.state.selectEpisodes = this.state.episodeList.map(function(item:any) {
          return item.id
        })
      }
      else if (newSelectedItems.includes(-1)) {
        // deselect all
        this.state.selectEpisodes = []
      }
      else {
        this.state.selectEpisodes = newSelectedItems
      }
      this.dispatch(related.setSelectedEpisodes(this.state.selectEpisodes))
    }
  }
})

</script>

<style lang="postcss">
</style>
