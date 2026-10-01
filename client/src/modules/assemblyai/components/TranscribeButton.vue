<template>
  <div>
    <button
      class="button button-primary"
      :disabled="disabled"
      @click="$emit('transcribe')"
    >
      {{ __('Start Transcription', 'podlove-podcasting-plugin-for-wordpress') }}
    </button>
    <p v-if="disabled" class="description" style="margin-top: 5px;">
      {{ __('No active audio file found for this episode. Please add an audio file first.', 'podlove-podcasting-plugin-for-wordpress') }}
    </p>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  emits: ['transcribe'],
})
</script>
