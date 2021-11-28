<template>
  <div class="max-h-96 overflow-x-auto">
    <div
      class="flex mb-2"
      v-for="(transcript, sindex) in transcripts"
      :key="`transcript-${sindex}`"
    >
      <div class="mr-2 w-12">
        <img class="w-12 h-12 rounded" :src="transcript?.voice?.avatar" />
      </div>
      <div class="w-full font-light text-sm mr-2">
        <span class="block font-bold">{{ transcript?.voice?.name }}</span>
        <span>
          <span
            v-for="(content, cindex) in transcript.content"
            :key="`transcript-${sindex}-content-${cindex}`"
          >
            {{ content.text }}
          </span>
        </span>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { last, dropRight, get } from 'lodash'
import { mapState } from 'redux-vuex'
import selectors from '@store/selectors'
import { PodloveTranscript, PodloveTranscriptVoice } from '@types/transcripts.types'
import { PodloveContributor } from '@types/contributors.types'

interface Transcript {
  voice: string
  content: {
    text: string
    start: number
    end: number
  }[]
}

export default {
  setup() {
    return {
      state: mapState({
        transcripts: selectors.transcripts.list,
        contributors: selectors.contributors.list,
        voices: selectors.transcripts.voices,
      }),
    }
  },

  computed: {
    voices() {
      return this.state.contributors.reduce(
        (result: { name: string; avatar: string }[], contributor: PodloveContributor) => {
          const voice = this.state.voices.find(
            (voice: PodloveTranscriptVoice) => voice.contributor === contributor.id
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

    transcripts() {
      return this.state.transcripts
        .reduce((result: Transcript[], transcript: PodloveTranscript) => {
          const lastTranscript = last(result)

          if (lastTranscript && lastTranscript.voice === transcript.voice) {
            return [
              ...dropRight(result),
              {
                ...lastTranscript,
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
              voice: transcript.voice,
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
          voice: get(this.voices, [transcript.voice]),
        }))
    },
  },
}
</script>

<style>
</style>
