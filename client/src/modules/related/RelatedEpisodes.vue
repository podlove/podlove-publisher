<template>
  <module name="relatedEpisodes" :title="__('Related episodes', 'podlove-podcasting-plugin-for-wordpress')">
    <div class="p-3">
      <div>
        <PodloveListbox
          placeholder="Select episode"
          :options="fullEpisodeList"
          :selectValues="state.selectEpisodes"
          @update = "updateRelEpisodes($event)"
          multiple
        />
      </div>
      <p class="mt-2 text-sm text-gray-500">{{ __('Select related episodes to this episode.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
    </div>
    <div class="pl-3 pb-3">
      <Tag v-for="name in selectEpisodeNames"
        :value="name.title"
        :id="Number(name.id)"
        @removeTag = "removeTag($event)"
        >
      </Tag>
    </div>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'

import Module from '@components/module/Module.vue'
import PodloveListbox, { OptionObject } from '@components/combobox/Combobox.vue'
import Tag from '@components/tag/Tag.vue';
import * as related from '@store/relatedEpisodes.store'

export default defineComponent({
  components: {
    Module,
    PodloveListbox,
    Tag,
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
    fullEpisodeList() : Array<OptionObject> {
      if (this.state.episodeList.length == 0)
        return this.state.episodeList
      if (this.state.selectEpisodes.length == 0) {
        const selectAllEpisodes = { id: 0, title: "Select all episodes"}
        return [selectAllEpisodes, ...this.state.episodeList]
      }
      else {
        const selectAllEpisodes = { id: -1, title: "Deselect all episodes"}
        return [selectAllEpisodes, ...this.state.episodeList]
      }
    },
    selectEpisodeNames() : Array<OptionObject> | null {
      return this.state.episodeList?.filter( (episode: OptionObject) => {
        return this.state.selectEpisodes.includes( episode.id )
      })
    }
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
    },
    removeTag(removeId: Number) {
      const idx = this.state.selectEpisodes.findIndex( (elem: Number) => {
        return elem == removeId;
      })
      this.state.selectEpisodes.splice(idx, 1);
    }
  }
})

</script>

<style lang="postcss">
</style>
