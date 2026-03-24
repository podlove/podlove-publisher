<template>
  <div v-if="files && files.length > 0">
    <p class="font-medium">{{ title }}</p>
    <ul class="mt-1 space-y-1">
      <TransferFileItem
        v-for="file in files"
        :key="file.filename"
        :file="file"
      />
    </ul>
  </div>
</template>

<script lang="ts">
import { defineComponent, type PropType } from 'vue'
import { PlusTransferFile } from '@store/auphonic.store'
import TransferFileItem from './TransferFileItem.vue'

export default defineComponent({
  name: 'TransferFileList',
  components: {
    TransferFileItem
  },
  props: {
    files: {
      type: Array as PropType<PlusTransferFile[]>,
      default: () => []
    },
    title: {
      type: String,
      default: ''
    }
  }
})
</script>
