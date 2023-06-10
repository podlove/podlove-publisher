<template>
  <div class="grid md:grid-cols-4 md:grid-rows-1 sm:grid-rows-4 p-3">
    <div class="mb-5 ml-5">
      <label for="soundbite-start" class="block text-sm font-medium text-gray-700">{{ __('Start') }}</label>
      <div class="mt-1">
        <input
          name="soundbite-start"
          type="text"
          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
          placeholder="00:00:00"
          :value="state.soundbite_start"
          @change="updateSoundbiteStart($event)"
        />
      </div>
    </div>
    <div class="mb-5 ml-5">
      <label for="soundbite-end" class="block text-sm font-medium text-gray-700">{{ __('End') }}</label
      >
      <div class="mt-1">
        <input
          name="soundbite-end"
          type="text"
          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
          placeholder="00:00:00"
          :value=soundbite_end
          @change="updateSoundbiteEnd($event)"
        />
      </div>
    </div>
    <div class="mb-5 ml-5">
      <label for="soundbite-duration" class="block text-sm font-medium text-gray-700">{{ __('Duration') }}</label>
      <div class="mt-1">
        <input
          name="soundbite-duration"
          type="text"
          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
          placeholder="00:00:00"
          :value="state.soundbite_duration"
          @change="updateSoundbiteDuration($event)"
        />
      </div>
    </div>
    <div class="mb-5 ml-5 md:mr-5">
      <label for="soundbite-title" class="block text-sm font-medium text-gray-700">{{ __('Soundbite title') }}
        <span class="text-xs">{{ __('(optional)') }}</span></label
      >
      <div class="mt-1">
        <input
          name="soundbite-title"
          type="text"
          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
          :value="state.soundbite_title"
          @change="updateSoundbiteTitle($event)"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'

import { selectors } from '@store'
import { update as updateEpisode } from '@store/episode.store'
import Timestamp from '@lib/timestamp'

export default defineComponent({
    setup() {
        return {
            state: mapState({
                soundbite_start: selectors.episode.soundbite_start,
                soundbite_duration: selectors.episode.soundbite_duration,
                soundbite_title: selectors.episode.soundbite_title,
            }),
            dispatch: injectStore().dispatch,
        }
    },

    computed: {
      soundbite_end(): string {
        if (this.state.soundbite_start != null && this.state.soundbite_duration != null) {
          let start = Timestamp.fromString(this.state.soundbite_start).totalMs
          let duration = Timestamp.fromString(this.state.soundbite_duration).totalMs
          let end = start + duration
          return new Timestamp(end).pretty
        }
      }
    },

    methods: {
      updateSoundbiteStart(event: Event) {
        const raw = (event.target as HTMLInputElement).value
        let value = Timestamp.fromString(raw).totalMs

        this.dispatch(
          updateEpisode({prop: 'soundbite_start', value: new Timestamp(value).pretty })
        )
      },
      updateSoundbiteEnd(event: Event) {
        const raw = (event.target as HTMLInputElement).value
        let end = Timestamp.fromString(raw).totalMs
        let start = Timestamp.fromString(this.state.soundbite_start).totalMs
        if (start < end) {
          let duration = end - start;
          this.dispatch(
            updateEpisode({prop: 'soundbite_duration', value: new Timestamp(duration).pretty })
          )
        }
      },
      updateSoundbiteDuration(event: Event) {
        const raw = (event.target as HTMLInputElement).value
        let value = Timestamp.fromString(raw).totalMs

        this.dispatch(
          updateEpisode({prop: 'soundbite_duration', value: new Timestamp(value).pretty })
        )
      },
      updateSoundbiteTitle(event: Event) {
        this.dispatch(
          updateEpisode({prop: 'soundbite_title', value: (event.target as HTMLInputElement).value })
        )
      },
    },
})

</script>

<style lang="postcss">
</style>
