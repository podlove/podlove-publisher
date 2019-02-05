<template>
  <div class="p-card">
    <div class="p-card-body" v-if="entry.state == 'unfurling'">
      <div class="main">
        <div class="site">
          <div class="loading-sitename"></div>
        </div>
        <span class="link">
          <div class="loading-link"></div>
        </span>
      </div>
      <div class="actions">
        <i class="podlove-icon-spinner rotate"></i>
      </div>
    </div>
    <div class="p-card-body" v-else-if="entry.url">
      <div class="main">
        <div class="site" v-if="entry.site_url && entry.site_name">
          <img v-if="entry.icon" :src="icon" alt="Site Icon" width="16" height="16">
          <div v-else class="default-icon"></div>
          <span class="site-name">
            <a :href="entry.site_url" target="_blank">{{ entry.site_name }}</a>
          </span>
        </div>
        <span class="link">
          <a :href="entry.url" target="_blank">{{ entry.title }}</a>
          <span class="disclose">▶</span>
        </span>
        <!-- TODO: disclosure triangle, off by default -->
        <div class="description">{{ entry.description }}</div>
      </div>
      <div class="actions">
        <a href="#" class="retry-btn" @click.prevent="unfurl()">refresh</a>
        <a href="#" class="delete-btn" @click.prevent="deleteEntry()">delete</a>
      </div>
    </div>
    <div class="p-card-body failed" v-else-if="entry.state == 'failed'">
      <div class="main">Unable to access URL: {{ entry.original_url }}</div>
      <div class="actions">
        <a href="#" class="retry-btn" @click.prevent="unfurl()">retry?</a>
        <a href="#" class="delete-btn" @click.prevent="deleteEntry()">delete</a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: ["entry"],
  data() {
    return {};
  },
  computed: {
    icon: function() {
      if (this.entry.icon[0] == "/") {
        return this.entry.site_url + this.entry.icon;
      } else {
        return this.entry.icon;
      }
    }
  },
  methods: {
    unfurl: function() {
      this.entry.state = "unfurling";

      $.post(
        podlove_vue.rest_url +
          "podlove/v1/shownotes/" +
          this.entry.id +
          "/unfurl"
      )
        .done(result => {
          this.$emit("update:entry", result);
        })
        .fail(({ responseJSON }) => {
          this.entry.state = "failed";
          console.error("could not unfurl entry:", responseJSON.message);
        });
    },
    deleteEntry: function() {
      this.$emit("delete:entry", this.entry);

      $.ajax({
        url: podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
        method: "DELETE",
        dataType: "json"
      })
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    }
  },
  mounted: function() {
    if (!this.entry.state) {
      this.unfurl();
    }
  }
};
</script>

<style>
</style>
