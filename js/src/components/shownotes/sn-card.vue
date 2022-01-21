<template>
  <div :class="htmlClasses">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: {
    htmlClass: {
      type: String,
      required: false,
    },
  },
  computed: {
    htmlClasses: function () {
      const defaultClasses = `
            shadow
            border border-1 border-gray-300
            px-3
            py-2.5
            mx-5
            my-3
            max-w-3xl
            rounded
            bg-white`;

      return `${defaultClasses} ${this.htmlClass}`;
    },
  },
};
</script>
