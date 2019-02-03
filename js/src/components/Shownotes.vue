<template>
  <div>Hello Shownotes</div>
</template>

<script>
export default {
  data() {
    return {
      shownotes: []
    };
  },
  mounted: function() {
    console.log("mounted");

    window.setTimeout(() => {
      // for development
      window.scroll(0, 3200);
    }, 1750);
  }
};
</script>

<style>
</style>


