<template>
  <div>
    <h1>Hello Shownotes</h1>

    <div class="p-card" v-show="ready" v-for="entry in shownotes" :key="entry.id">
      <div class="p-card-body">
        <span class="link-title" v-if="entry.title">{{ entry.title }}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      shownotes: [{ id: 1, title: "Hello World" }],
      ready: true
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
.link-title {
  font-weight: bold;
  font-size: 15px;
}
</style>


