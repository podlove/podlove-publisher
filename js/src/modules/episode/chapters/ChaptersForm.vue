<template>
  <div class="flex">
    <div>
      <ol>
        <li v-for="(chapter, index) in state.chapters" :key="`chapter-${index}`">{{ chapter.title }}</li>
      </ol>
    </div>
    <div>

    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from "vue";
import { mapState } from 'redux-vuex'
import { selectors } from '@store'

export default defineComponent({
  setup() {
    return {
      state: mapState({
        chapters: selectors.chapters.list
      })
    }
  },
})
</script>

<style></style>
