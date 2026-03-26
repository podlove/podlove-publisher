<template>
  <module name="assemblyai" :title="__('AssemblyAI Transcription', 'podlove-podcasting-plugin-for-wordpress')">
    <div class="m-7">
      <!-- Idle: show transcribe button -->
      <div v-if="status === 'idle'">
        <TranscribeButton :disabled="!hasMediaFiles" @transcribe="onTranscribe" />
      </div>

      <!-- Submitting -->
      <div v-else-if="status === 'submitting'" class="flex items-center gap-2">
        <span class="spinner is-active" style="float: none;"></span>
        <span>{{ __('Submitting to AssemblyAI...', 'podlove-podcasting-plugin-for-wordpress') }}</span>
      </div>

      <!-- Processing -->
      <div v-else-if="status === 'processing'" class="flex items-center gap-2">
        <span class="spinner is-active" style="float: none;"></span>
        <span>{{ __('Transcribing...', 'podlove-podcasting-plugin-for-wordpress') }} ({{ assemblyaiStatusLabel }})</span>
      </div>

      <!-- Importing -->
      <div v-else-if="status === 'importing'" class="flex items-center gap-2">
        <span class="spinner is-active" style="float: none;"></span>
        <span>{{ __('Importing transcript...', 'podlove-podcasting-plugin-for-wordpress') }}</span>
      </div>

      <!-- Imported -->
      <div v-else-if="status === 'imported'">
        <p class="text-green-600">
          {{ __('Transcript imported successfully.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>
        <button class="button mt-1" @click="onReset">
          {{ __('Transcribe Again', 'podlove-podcasting-plugin-for-wordpress') }}
        </button>
      </div>

      <!-- Error -->
      <div v-else-if="status === 'error'">
        <p class="text-red-600 mb-2">
          {{ error || __('An error occurred.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>
        <button class="button" @click="onReset">
          {{ __('Retry', 'podlove-podcasting-plugin-for-wordpress') }}
        </button>
      </div>
    </div>

    <modal :open="confirmModalVisible" @close="closeConfirmModal()">
      <div class="sm:flex sm:items-start">
        <div
          class="
            mx-auto
            flex-shrink-0 flex
            items-center
            justify-center
            h-12
            w-12
            rounded-full
            bg-yellow-100
            sm:mx-0 sm:h-10 sm:w-10
          "
        >
          <exclamation-icon class="h-6 w-6 text-yellow-600" aria-hidden="true" />
        </div>
        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
          <DialogTitle as="h3" class="text-lg leading-6 font-medium text-gray-900">
            {{ __('Replace Transcript', 'podlove-podcasting-plugin-for-wordpress') }}
          </DialogTitle>
          <div class="mt-2">
            <p class="text-sm text-gray-500">
              {{ __('This episode already has a transcript. Starting a new transcription will replace it.', 'podlove-podcasting-plugin-for-wordpress') }}
            </p>
          </div>
        </div>
      </div>
      <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
        <podlove-button class="sm:ml-3 sm:w-auto sm:text-sm" variant="danger" @click="confirmTranscribe()">
          {{ __('Replace', 'podlove-podcasting-plugin-for-wordpress') }}
        </podlove-button>
        <podlove-button class="sm:ml-3 sm:w-auto sm:text-sm" @click="closeConfirmModal()">
          {{ __('Cancel', 'podlove-podcasting-plugin-for-wordpress') }}
        </podlove-button>
      </div>
    </modal>
  </module>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { selectors } from '@store'
import { injectStore, mapState } from 'redux-vuex'
import * as assemblyai from '@store/assemblyai.store'
import { ExclamationTriangleIcon as ExclamationIcon } from '@heroicons/vue/24/outline'
import { DialogTitle } from '@headlessui/vue'
import Module from '@components/module/Module.vue'
import Modal from '@components/modal/Modal.vue'
import PodloveButton from '@components/button/Button.vue'
import TranscribeButton from './components/TranscribeButton.vue'

export default defineComponent({
  components: {
    Module,
    Modal,
    PodloveButton,
    TranscribeButton,
    ExclamationIcon,
    DialogTitle,
  },

  data() {
    return {
      confirmModalVisible: false,
    }
  },

  setup() {
    const store = injectStore()

    return {
      state: mapState({
        status: selectors.assemblyai.status,
        error: selectors.assemblyai.error,
        assemblyai_status: selectors.assemblyai.assemblyai_status,
        mediafiles: selectors.mediafiles.files,
        transcripts: selectors.transcripts.list,
      }),
      dispatch: store.dispatch,
    }
  },

  computed: {
    status(): string {
      return this.state.status
    },
    error(): string | null {
      return this.state.error
    },
    hasMediaFiles(): boolean {
      const files = this.state.mediafiles || []
      return files.some((f: any) => f.enable)
    },
    assemblyaiStatusLabel(): string {
      const status = this.state.assemblyai_status
      if (status === 'queued') return this.__('queued', 'podlove-podcasting-plugin-for-wordpress')
      if (status === 'processing') return this.__('processing', 'podlove-podcasting-plugin-for-wordpress')
      return status || ''
    },
  },

  created() {
    this.dispatch(assemblyai.init())
  },

  methods: {
    onTranscribe() {
      const hasTranscripts = this.state.transcripts && this.state.transcripts.length > 0

      if (hasTranscripts) {
        this.confirmModalVisible = true
        return
      }

      this.dispatch(assemblyai.startTranscription())
    },
    confirmTranscribe() {
      this.closeConfirmModal()
      this.dispatch(assemblyai.startTranscription())
    },
    closeConfirmModal() {
      this.confirmModalVisible = false
    },
    onReset() {
      this.dispatch(assemblyai.setStatus('idle'))
      this.dispatch(assemblyai.setError(null))
    },
  },
})
</script>
