<template>
  <div v-if="state.voices.length > 0">
    <podlove-button variant="secondary" size="small" @click="openVoices()">{{ __('Voices') }}</podlove-button>
    <modal :open="modalOpen" @close="closeVoices()">
      <div class="border-gray-200 border-b pb-2 px-4 -mx-6 mb-4">
        <h3 class="text-lg leading-6 font-medium text-gray-900">{{ __('Transcript Voices') }}</h3>
      </div>
      <div
        v-for="(voice, vindex) in state.voices"
        :key="`voice-${vindex}`"
        class="w-full py-2 px-4"
        :class="{ 'bg-white': vindex % 2 }"
      >
        <label for="country" class="block text-sm font-medium text-gray-700">{{
          voice.voice
        }}</label>
        <select
          :value="voice.contributor"
          class="
            mt-1
            block
            w-full
            py-2
            px-3
            border border-gray-300
            bg-white
            rounded-md
            shadow-sm
            focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
            sm:text-sm
          "
          @change="updateContributor(voice.voice, $event)"
        >
          <option value="0"></option>
          <option
            v-for="(contributor, kindex) in state.contributors"
            :key="`voice-${vindex}-contributor-${kindex}`"
            :value="contributor.id"
          >
            {{ contributor.name }}
          </option>
        </select>
      </div>
    </modal>
  </div>
</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'
import { injectStore, mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { updateVoice } from '@store/transcripts.store'
import Modal from '@components/modal/Modal.vue'
import PodloveButton from '@components/button/Button.vue'

export default defineComponent({
  components: {
    PodloveButton,
    Modal,
  },
  data() {
    return {
      modalOpen: false,
    }
  },
  setup() {
    return {
      state: mapState({
        contributors: selectors.contributors.contributors,
        voices: selectors.transcripts.voices,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateContributor(voice: string, event: Event) {
      const contributor = (event.target as HTMLInputElement).value
      this.dispatch(updateVoice({ voice, contributor }))
    },

    openVoices() {
      this.modalOpen = true
    },

    closeVoices() {
      this.modalOpen = false
    },
  },
})
</script>
