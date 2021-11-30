<template>
  <div>
    <div v-for="(voice, vindex) in state.voices" :key="`voice-${vindex}`" class="w-full flex py-2 px-4" :class="{ 'bg-white': vindex % 2 }">
      <div class="font-mono w-36 py-2">{{ voice.voice }}</div>
      <div>
        <select :value="voice.contributor" class="font-normal bg-transparent px-1 py-2" @change="updateContributor(voice.voice, $event.target.value)">
          <option value="0"></option>
          <option v-for="(contributor, kindex) in state.contributors" :key="`voice-${vindex}-contributor-${kindex}`" :value="contributor.id">{{ contributor.name }}</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { updateVoice } from '@store/transcripts.store'

export default {
  setup() {
    return {
      state: mapState({
        contributors: selectors.contributors.list,
        voices: selectors.transcripts.voices,
      }),
      dispatch: injectStore().dispatch
    }
  },

  methods: {
    updateContributor(voice: string, contributor: string) {
      this.dispatch(updateVoice({ voice, contributor }))
    }
  }
}
</script>
