<template>
  <div class="max-w-3xl">
    <div v-if="entry.type == 'link'">
      <shownotes-link v-bind:entry="entry"></shownotes-link>
    </div>
    <div v-if="entry.type == 'topic'">
      <shownotes-topic v-bind:entry="entry"></shownotes-topic>
    </div>
  </div>
</template>

<script>
import ShownotesTopic from "./shownotes/topic";
import ShownotesLink from "./shownotes/link";

export default {
  props: ["entry"],
  data() {
    return {};
  },
  computed: {
    isHidden: function () {
      return this.entry.hidden === "1";
    },
  },
  components: {
    "shownotes-topic": ShownotesTopic,
    "shownotes-link": ShownotesLink,
  },
};
</script>
