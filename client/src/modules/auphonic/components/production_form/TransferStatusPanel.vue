<template>
  <!-- Waiting for webhook -->
  <div class="rounded-md bg-blue-50 p-4" v-if="status === 'waiting_for_webhook'">
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
            @click="$emit('action')"
            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
          >
            {{ __('Transfer Now', 'podlove-podcasting-plugin-for-wordpress') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- In progress -->
  <div class="rounded-md bg-gray-50 p-4" v-else-if="status === 'in_progress'">
    <div class="flex">
      <div class="flex-shrink-0">
        <ArrowPathIcon class="h-5 w-5 text-gray-400 animate-spin" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-gray-800">{{ __('Transferring Files', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
        <div class="mt-2 text-sm text-gray-700">
          <p>{{ __('Files are being transferred to PLUS storage...', 'podlove-podcasting-plugin-for-wordpress') }}</p>
          <div class="mt-3" v-if="files && files.length > 0">
            <ul class="space-y-1">
              <TransferFileItem
                v-for="file in files"
                :key="file.filename"
                :file="file"
              />
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Completed -->
  <div class="rounded-md bg-green-50 p-4" v-else-if="status === 'completed'">
    <div class="flex">
      <div class="flex-shrink-0">
        <CheckCircleIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-green-800">{{ __('Transfer Complete', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
        <div class="mt-2 text-sm text-green-700">
          <p>{{ __('All files have been transferred successfully to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
          <TransferFileList
            v-if="files && files.length > 0"
            :files="files"
            :title="__('Transferred files:', 'podlove-podcasting-plugin-for-wordpress')"
            class="mt-2"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- Completed with errors -->
  <div class="rounded-md bg-yellow-50 p-4" v-else-if="status === 'completed_with_errors'">
    <div class="flex">
      <div class="flex-shrink-0">
        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-yellow-800">{{ __('Transfer Completed with Errors', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
        <div class="mt-2 text-sm text-yellow-700">
          <p>{{ __('Some files were transferred successfully, but others failed.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
          <TransferFileList
            v-if="files && files.length > 0"
            :files="files"
            :title="__('File transfer results:', 'podlove-podcasting-plugin-for-wordpress')"
            class="mt-2"
          />
        </div>
        <div class="mt-4">
          <button
            @click="$emit('action')"
            class="inline-flex items-center rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600"
          >
            {{ __('Retry Failed Transfers', 'podlove-podcasting-plugin-for-wordpress') }}
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Failed -->
  <div class="rounded-md bg-red-50 p-4" v-else-if="status === 'failed'">
    <div class="flex">
      <div class="flex-shrink-0">
        <XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
      </div>
      <div class="ml-3">
        <h3 class="text-sm font-medium text-red-800">{{ __('Transfer Failed', 'podlove-podcasting-plugin-for-wordpress') }}</h3>
        <div class="mt-2 text-sm text-red-700">
          <p>{{ __('Some files failed to transfer to PLUS storage.', 'podlove-podcasting-plugin-for-wordpress') }}</p>
          <div class="mt-2" v-if="errors">
            <p class="font-medium">{{ __('Error details:', 'podlove-podcasting-plugin-for-wordpress') }}</p>
            <p class="text-sm text-red-600">{{ errors }}</p>
          </div>
          <div class="mt-2" v-if="files && files.length > 0">
            <p class="font-medium">{{ __('File transfer results:', 'podlove-podcasting-plugin-for-wordpress') }}</p>
            <ul class="mt-1 list-disc list-inside">
              <li v-for="file in files" :key="file.filename" class="text-sm">
                <span class="font-medium">{{ file.filename }}</span>
                <span v-if="file.success" class="text-green-600 ml-2">✓</span>
                <span v-else class="text-red-600 ml-2">✗ {{ file.message }}</span>
              </li>
            </ul>
          </div>
        </div>
        <div class="mt-4">
          <button
            @click="$emit('action')"
            class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
          >
            {{ __('Retry Transfer', 'podlove-podcasting-plugin-for-wordpress') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { PlusTransferFile } from '@store/auphonic.store'
import {
  ClockIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import TransferFileList from './TransferFileList.vue'
import TransferFileItem from './TransferFileItem.vue'

export default defineComponent({
  name: 'TransferStatusPanel',
  components: {
    ClockIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    TransferFileList,
    TransferFileItem,
  },
  props: {
    status: {
      type: String as PropType<'waiting_for_webhook' | 'in_progress' | 'completed' | 'completed_with_errors' | 'failed'>,
      required: true
    },
    files: {
      type: Array as PropType<PlusTransferFile[]>,
      default: () => []
    },
    errors: {
      type: String,
      default: undefined
    }
  },
  emits: ['action']
})
</script>
