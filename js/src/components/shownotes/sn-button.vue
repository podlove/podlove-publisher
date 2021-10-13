<template>
  <button type="button" :class="htmlClasses" @click.prevent="onClick">
    <slot></slot>
  </button>
</template>

<script>
export default {
  props: {
    htmlClass: {
      type: String,
      required: false,
      default: "",
    },
    onClick: {
      type: Function,
      required: true,
    },
    type: {
      type: String,
      required: false,
      default: "secondary",
    },
  },
  computed: {
    htmlClasses: function () {
      const defaultClasses = `bg-white
      py-2
      px-4
      border
      rounded-md
      shadow-sm
      text-sm
      font-medium
      focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500
      cursor-pointer`;

      let typeClasses = "";

      switch (this.type) {
        case "primary":
          typeClasses =
            "text-white border-gray-300 bg-blue-600 hover:bg-blue-700";
          break;
        case "secondary":
          typeClasses =
            "bg-white border-gray-300 hover:bg-gray-50 text-gray-700";
          break;
        case "danger":
          typeClasses =
            "border border-transparent text-red-700 bg-red-50 hover:bg-red-200 focus:ring-red-500";
          break;
        default:
          break;
      }
      return `${defaultClasses} ${typeClasses} ${this.htmlClass}`;
    },
  },
};
</script>
