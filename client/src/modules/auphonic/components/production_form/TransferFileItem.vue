<template>
  <li class="flex items-center text-sm">
    <span class="flex-shrink-0 mr-2">
      <ArrowPathIcon v-if="file.status === 'processing'" class="h-4 w-4 text-blue-500 animate-spin" />
      <CheckCircleIcon v-else-if="file.status === 'completed'" class="h-4 w-4 text-green-500" />
      <XCircleIcon v-else-if="file.status === 'failed'" class="h-4 w-4 text-red-500" />
      <ClockIcon v-else class="h-4 w-4 text-gray-400" />
    </span>
    <span class="font-medium">{{ file.filename }}</span>
    <span class="ml-2 text-xs" :class="{
      'text-blue-600': file.status === 'processing',
      'text-green-600': file.status === 'completed',
      'text-red-600': file.status === 'failed',
      'text-gray-500': file.status === 'pending'
    }">
      {{ getFileStatusMessage(file) }}
    </span>
  </li>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { PlusTransferFile } from '@store/auphonic.store'
import {
  ClockIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

export default defineComponent({
  name: 'TransferFileItem',
  components: {
    ClockIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
  },
  props: {
    file: {
      type: Object as PropType<PlusTransferFile>,
      required: true
    }
  },
  methods: {
    getFileStatusMessage(file: PlusTransferFile): string {
      switch (file.status) {
        case 'pending':
          return 'Waiting...'
        case 'processing':
          return 'Transferring...'
        case 'completed':
          return 'Completed'
        case 'failed':
          return file.message || 'Failed'
        default:
          return file.message || ''
      }
    },
  }
})
</script>
