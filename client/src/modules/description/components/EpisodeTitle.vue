<template>
  <div>
    <label for="episode-title" class="block text-sm font-medium text-gray-700">{{ __('Title', 'podlove-podcasting-plugin-for-wordpress') }}</label>
    <div class="mt-1">
      <input
        name="episode-title"
        type="text"
        class="
          shadow-sm
          focus:ring-indigo-500 focus:border-indigo-500
          block
          w-full
          sm:text-sm
          border-gray-300
          rounded-md
        "
        :value="state.title"
        :placeholder="state.post_title || undefined"
        @input="updateTitle($event)"
      />
    </div>
    <p class="mt-2 text-sm text-gray-500">{{ __('Clear, concise name for your episode. It is recommended to not include the podcast title, episode number, season number or date in this tag.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'
import Module from '@components/module/Module.vue'
import { injectAppDispatch, mapAppState } from '@store/vue'

export default defineComponent({
  components: {
    Module,
  },

  setup() {
    return {
      state: mapAppState({
        title: selectors.episode.title,
        post_title: selectors.post.title
      }),
      dispatch: injectAppDispatch(),
    }
  },

  methods: {
    updateTitle(event: Event) {
      this.dispatch(
        updateEpisode({ prop: 'title', value: (event.target as HTMLInputElement).value })
      )
    },
  },
})
</script>



<style>
</style>
