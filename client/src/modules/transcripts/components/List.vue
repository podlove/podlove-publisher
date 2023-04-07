<template>
  <div class="h-96 p-2 overflow-x-auto" v-if="transcripts.length > 0">
    <div
      class="flex mb-2"
      v-for="(transcript, sindex) in transcripts"
      :key="`transcript-${sindex}`"
    >
      <div class="mr-2 w-12 text-gray-400">
        <img
          class="w-12 h-12 rounded"
          v-if="transcript?.voice?.avatar"
          :src="transcript?.voice?.avatar"
        />
        <avatar v-else />
      </div>
      <div class="w-full font-light text-sm mr-2">
        <span class="block font-bold">{{ transcript?.voice?.name }}</span>
        <span>
          <span
            class="mr-1"
            v-for="(content, cindex) in transcript.content"
            :key="`transcript-${sindex}-content-${cindex}`"
          >
            {{ content.text }}
          </span>
        </span>
      </div>
    </div>
  </div>
  <div v-else class="text-center h-96 flex items-center justify-center flex-col">
    <document-text-icon class="mx-auto h-12 w-12 text-gray-400" />

    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No: transcripts') }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ __('Get started by importing a transcript.') }}</p>
    <div class="mt-6">
      <transcripts-import outlet="content" class="mr-1" />
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from '@vue/runtime-core'
import { last, dropRight, get } from 'lodash'
import { mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { DocumentTextIcon } from '@heroicons/vue/outline'

import Avatar from '@components/icons/Avatar.vue'

import { PodloveTranscript } from '../../../types/transcripts.types'
import { PodloveContributor } from '../../../types/contributors.types'

import TranscriptsImport from './Import.vue'

interface Transcript {
  voiceId: string
  voice: {
    avatar: string
    name: string
  }
  content: {
    text: string
    start: number
    end: number
  }[]
}

export default defineComponent({
  components: {
    Avatar, TranscriptsImport, DocumentTextIcon
  },

  setup() {
    return {
      state: mapState({
        transcripts: selectors.transcripts.list,
        contributors: selectors.contributors.contributors,
        voices: selectors.transcripts.voices,
      }),
    }
  },

  computed: {
    voices(): { [key: string]: { id: string; name: string; avatar: string } }[] {
      return this.state.contributors.reduce(
        (result: { name: string; avatar: string }[], contributor: PodloveContributor) => {
          const voice = this.state.voices.find(
            (voice: { voice: string; contributor: string }) => voice.contributor === contributor.id
          )?.voice

          return {
            ...result,
            [voice]: {
              id: contributor.id,
              name: contributor.name,
              avatar: contributor.avatar,
            },
          }
        },
        {}
      )
    },

    transcripts(): Transcript[] {
      return this.state.transcripts
        .reduce((result: Transcript[], transcript: PodloveTranscript) => {
          const lastTranscript = last(result)
          if (lastTranscript && lastTranscript.voiceId === transcript.voice) {
            return [
              ...dropRight(result),
              {
                ...lastTranscript,
                voiceId: transcript.voice,
                content: [
                  ...lastTranscript.content,
                  {
                    text: transcript.text,
                    start: transcript.start_ms,
                    end: transcript.end_ms,
                  },
                ],
              },
            ]
          }

          return [
            ...result,
            {
              voiceId: transcript.voice,
              content: [
                {
                  text: transcript.text,
                  start: transcript.start_ms,
                  end: transcript.end_ms,
                },
              ],
            },
          ]
        }, [])
        .map((transcript: Transcript) => ({
          ...transcript,
          voice: get(this.voices, [transcript.voiceId], { name: transcript.voiceId }),
        }))
    },
  },
})
</script>

<style>
</style>
