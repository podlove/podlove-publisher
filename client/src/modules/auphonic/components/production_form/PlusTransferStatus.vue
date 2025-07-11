<template>
  <div class="mt-4 overflow-hidden rounded-lg bg-white shadow" v-if="showPlusTransferStatus">
    <div class="p-6">
      <div class="mb-4">
        <h3 class="text-lg font-medium leading-6 text-gray-900">PLUS File Storage</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ __('Auphonic files are automatically transferred to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}
        </p>
      </div>

      <!-- Waiting for webhook -->
      <div class="rounded-md bg-blue-50 p-4" v-if="plusTransferStatus === 'waiting_for_webhook' || plusTransferStatus === undefined">
        <div class="flex">
          <div class="flex-shrink-0">
            <ClockIcon class="h-5 w-5 text-blue-400" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">{{ __('Waiting for Transfer', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>{{ __('Files will be transferred automatically when the production is completed.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
            </div>
            <div class="mt-4">
              <button
                @click="triggerManualTransfer"
                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
              >
                {{ __('Transfer Now', 'podlove-podcasting-plugin-for-wordpress') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- In progress -->
      <div class="rounded-md bg-yellow-50 p-4" v-if="plusTransferStatus === 'in_progress'">
        <div class="flex">
          <div class="flex-shrink-0">
            <ArrowPathIcon class="h-5 w-5 text-yellow-400 animate-spin" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">{{ __('Transferring Files', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>{{ __('Files are being transferred to PLUS storage...', 'podlove-podcasting-plugin-for-wordpress') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Completed -->
      <div class="rounded-md bg-green-50 p-4" v-if="plusTransferStatus === 'completed'">
        <div class="flex">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-green-800">{{ __('Transfer Complete', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
            <div class="mt-2 text-sm text-green-700">
              <p>{{ __('Files have been transferred successfully to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
              <div class="mt-2" v-if="plusTransferFiles && plusTransferFiles.length > 0">
                <p class="font-medium">{{ __('Transferred files:', 'podlove-podcasting-plugin-for-wordpress') }}</p>
                <ul class="mt-1 list-disc list-inside">
                  <li v-for="file in plusTransferFiles" :key="file.filename" class="text-sm">
                    <span class="font-medium">{{ file.filename }}</span>
                    <span v-if="file.success" class="text-green-600 ml-2">✓</span>
                    <span v-else class="text-red-600 ml-2">✗</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Failed -->
      <div class="rounded-md bg-red-50 p-4" v-if="plusTransferStatus === 'failed'">
        <div class="flex">
          <div class="flex-shrink-0">
            <XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">{{ __('Transfer Failed', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ __('Some files failed to transfer to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
              <div class="mt-2" v-if="plusTransferErrors">
                <p class="font-medium">{{ __('Error details:', 'podlove-podcasting-plugin-for-wordpress') }}</p>
                <p class="text-sm text-red-600">{{ plusTransferErrors }}</p>
              </div>
              <div class="mt-2" v-if="plusTransferFiles && plusTransferFiles.length > 0">
                <p class="font-medium">{{ __('File transfer results:', 'podlove-podcasting-plugin-for-wordpress') }}</p>
                <ul class="mt-1 list-disc list-inside">
                  <li v-for="file in plusTransferFiles" :key="file.filename" class="text-sm">
                    <span class="font-medium">{{ file.filename }}</span>
                    <span v-if="file.success" class="text-green-600 ml-2">✓</span>
                    <span v-else class="text-red-600 ml-2">✗ {{ file.message }}</span>
                  </li>
                </ul>
              </div>
            </div>
            <div class="mt-4">
              <button
                @click="triggerManualTransfer"
                class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
              >
                {{ __('Retry Transfer', 'podlove-podcasting-plugin-for-wordpress') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { injectStore, mapState } from 'redux-vuex'
import { selectors } from '@store'
import { PlusTransferFile, triggerPlusTransfer, loadPlusTransferStatus } from '@store/auphonic.store'
import { verifyAll } from '@store/mediafiles.store'
import type { Production } from '@store/auphonic.store'

import {
  ClockIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

export default defineComponent({
  components: {
    ClockIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
  },

  setup() {
    return {
      state: mapState({
        production: selectors.auphonic.production,
        plusTransferStatus: selectors.auphonic.plusTransferStatus,
        plusTransferFiles: selectors.auphonic.plusTransferFiles,
        plusTransferErrors: selectors.auphonic.plusTransferErrors,
      }),
      dispatch: injectStore().dispatch,
    }
  },

  mounted() {
    this.loadPlusTransferStatus()
  },

  methods: {
    loadPlusTransferStatus() {
      if (!this.production.uuid) return

      this.dispatch(loadPlusTransferStatus({
        production_uuid: this.production.uuid
      }))
    },

    triggerManualTransfer() {
      if (this.production.uuid) {
        this.dispatch(triggerPlusTransfer({ production_uuid: this.production.uuid }))
      }
    },

    refreshEpisodeData() {
      this.dispatch(verifyAll())
    },
  },

  computed: {
    production(): Production {
      return this.state.production || {}
    },
    plusTransferStatus(): 'waiting_for_webhook' | 'in_progress' | 'completed' | 'failed' | undefined {
      return this.state.plusTransferStatus
    },
    plusTransferFiles(): PlusTransferFile[] | undefined {
      return this.state.plusTransferFiles
    },
    plusTransferErrors(): string | undefined {
      return this.state.plusTransferErrors
    },
    showPlusTransferStatus(): boolean {
      return this.production.status === 3
    },
  },
})
</script>
