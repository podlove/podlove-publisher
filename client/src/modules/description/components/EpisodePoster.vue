<template>
  <div>
    <modal size="medium" :open="modalOpen" @close="closeModal()">
      <div class="border-gray-200 border-b pb-2 px-4 -mx-6 mb-4">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
          {{ __('Episode Poster', 'podlove-podcasting-plugin-for-wordpress') }}
        </h3>
      </div>
      <div class="relative">
        <input
          name="episode-poster"
          type="text"
          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md pr-8"
          :value="state.episodePoster"
          @change="updatePoster($event)"
        />
        <button
          class="absolute right-2 top-1/2 -mt-3 text-gray-400 hover:text-gray-700"
          :title="__('Clear Input', 'podlove-podcasting-plugin-for-wordpress')"
          @click="updatePoster(null)"
        >
          <x-icon class="w-6 h-6" />
        </button>
      </div>
      <p class="mt-2 text-sm text-gray-500">
        {{
          __(
            'Enter URL or select image from media library. Apple/iTunes recommends 3000 x 3000 pixel JPG or PNG',
            'podlove-podcasting-plugin-for-wordpress'
          )
        }}
      </p>
      <p class="mt-2 flex justify-end">
        <podlove-button variant="primary" @click="closeModal()">{{
          __('Use this URL', 'podlove-podcasting-plugin-for-wordpress')
        }}</podlove-button>
      </p>
    </modal>

    <label class="block text-sm font-medium text-gray-700">{{
      __('Poster', 'podlove-podcasting-plugin-for-wordpress')
    }}</label>
    <div
      class="border shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block relative w-44 h-44 sm:text-sm border-gray-300 rounded-md mt-1 overflow-hidden bg-cover bg-no-repeat bg-center"
      :style="posterStyle"
    >
      <div
        class="absolute z-10 left-0 top-0 w-full h-full flex justify-center items-center bg-white bg-opacity-40 hover:opacity-0 text-gray-500 opacity-100"
        v-if="state.asset === 'manual'"
      >
        <pencil-icon class="h-6 w-6" aria-hidden="true" />
      </div>
      <div
        class="absolute z-10 left-0 top-0 w-full h-full flex flex-col justify-center items-center bg-white bg-opacity-50 text-gray-500 opacity-0 hover:opacity-100"
        v-if="state.asset === 'manual'"
      >
        <podlove-button @click="openModal()" class="w-32 mb-2"
          ><span class="w-full text-center">{{
            __('URL', 'podlove-podcasting-plugin-for-wordpress')
          }}</span></podlove-button
        >
        <podlove-button class="w-32 text-center mb-2" @click="selectImage()"
          ><span class="w-full text-center">{{
            __('Media', 'podlove-podcasting-plugin-for-wordpress')
          }}</span></podlove-button
        >
        <podlove-button
          variant="danger"
          class="w-32 text-center"
          :disabled="state.episodePoster === null"
          @click="updatePoster(null)"
          ><span class="w-full text-center">{{
            __('Reset', 'podlove-podcasting-plugin-for-wordpress')
          }}</span></podlove-button
        >
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { injectStore, mapState } from 'redux-vuex'
import { StyleValue, defineComponent } from 'vue'
import { selectors } from '@store'
import { PencilIcon, XMarkIcon as XIcon } from '@heroicons/vue/24/outline'
import { update as updateEpisode, selectPoster as selectEpisodePoster } from '@store/episode.store'

import Modal from '@components/modal/Modal.vue'
import PodloveButton from '@components/button/Button.vue'
import { get } from 'lodash'

export default defineComponent({
  components: {
    PodloveButton,
    Modal,
    PencilIcon,
    XIcon,
  },

  setup() {
    return {
      state: mapState({
        poster: selectors.episode.poster,
        episodePoster: selectors.episode.episodePoster,
        asset: selectors.settings.imageAsset,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  data() {
    return {
      modalOpen: false,
      inputValue: null,
    }
  },

  computed: {
    posterStyle(): StyleValue {
      const url = this.state.episodePoster || this.state.poster

      if (url === null) {
        return {}
      }

      return { 'background-image': `url(${url})` }
    },
  },

  methods: {
    openModal() {
      this.modalOpen = true
    },

    closeModal() {
      this.modalOpen = false
    },

    selectImage() {
      this.dispatch(selectEpisodePoster())
    },

    updatePoster(event: Event | null) {
      const value = get(event, ['target', 'value'], null)

      this.dispatch(updateEpisode({ prop: 'episode_poster', value }))
    },
  },
})
</script>

<style></style>
