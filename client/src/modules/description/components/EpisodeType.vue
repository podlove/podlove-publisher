<template>
  <div>
    <label for="episode-type" class="block text-sm font-medium text-gray-700">{{
      __('Type', 'podlove-podcasting-plugin-for-wordpress')
    }}</label>
    <div class="mt-1">
      <select
        name="episode-type"
        class="
          block
          w-full
          pl-3
          pr-10
          py-2
          text-base
          border-gray-300
          focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
          sm:text-sm
          rounded-md
        "
        :value="state.type"
        @input="updateType($event)"
      >
        <option>{{ __('Please choose ...', 'podlove-podcasting-plugin-for-wordpress') }}</option>
        <option value="full">{{ __('full (complete content of an episode)', 'podlove-podcasting-plugin-for-wordpress') }}</option>
        <option value="trailer">{{ __('trailer (short, promotional piece of content that represents a preview of an episode)', 'podlove-podcasting-plugin-for-wordpress') }}</option>
        <option value="bonus">{{ __('bonus (extra content for an episode, for example behind the scenes information)', 'podlove-podcasting-plugin-for-wordpress') }}</option>
      </select>
    </div>
    <p class="mt-2 text-sm text-gray-500">
      {{ __('Episode type. May be used by podcast clients.', 'podlove-podcasting-plugin-for-wordpress') }}
    </p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, injectStore } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'
import Module from '@components/module/Module.vue'

export default defineComponent({
  components: {
    Module,
  },

  setup() {
    return {
      state: mapState({
        type: selectors.episode.type,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  methods: {
    updateType(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'type', value: (event.target as HTMLInputElement).value })
      )
    },
  },
})
</script>



<style>
</style>
