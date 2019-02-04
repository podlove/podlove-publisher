<template>
  <div>
    <div class="p-card" v-show="ready" v-for="entry in shownotes" :key="entry.id">
      <div class="p-card-body">
        <div class="site">
          <img v-if="entry.icon" :src="entry.icon" alt="Site Icon" width="16" height="16">
          <div v-else class="default-icon"></div>
          <span class="site-name">
            <a :href="entry.site_url" target="_blank">{{ entry.site_name }}</a>
          </span>
        </div>
        <span class="link">
          <a :href="entry.original_url" target="_blank">{{ entry.title }}</a>
        </span>
      </div>
    </div>

    <div class="p-card create-card" v-if="mode == 'create'">
      <div class="p-card-body">
        <input
          @keyup.enter="createEntry"
          v-model="newUrl"
          type="text"
          name="new_entry"
          id="new_entry"
          placeholder="https://example.com"
          :disabled="mode == 'create-waiting'"
        >
        <button
          type="button"
          class="button button-primary"
          @click.prevent="createEntry"
          :disabled="mode == 'create-waiting'"
        >Add</button>
      </div>
    </div>

    <button
      type="button"
      class="button create-button"
      @click.prevent="mode = 'create'"
      v-if="mode != 'create'"
    >Add Entry</button>
  </div>
</template>

<script>
const $ = jQuery;

export default {
  props: ["episodeid"],
  data() {
    return {
      shownotes: [],
      ready: false,
      mode: "idle",
      newUrl: ""
    };
  },
  methods: {
    createEntry: function() {
      if (!this.newUrl) return;

      this.mode = "create-waiting";

      $.post(podlove_vue.rest_url + "podlove/v1/shownotes", {
        original_url: this.newUrl,
        episode_id: this.episodeid
      })
        .done(result => {
          this.shownotes.push(result);
          this.newUrl = "";
          this.mode = "idle";
        })
        .fail(({ responseJSON }) => {
          console.error("could not create entry:", responseJSON.message);
          this.mode = "idle";
        });
    }
  },
  mounted: function() {
    $.getJSON(
      podlove_vue.rest_url + "podlove/v1/shownotes?episode_id=" + this.episodeid
    )
      .done(shownotes => {
        this.shownotes = shownotes;
        this.ready = true;
      })
      .fail(({ responseJSON }) => {
        console.error("could not load shownotes:", responseJSON.message);
      });

    window.setTimeout(() => {
      // for development
      window.scroll(0, 3200);
    }, 1750);
  }
};
</script>

<style>
.p-card {
  margin-bottom: 6px;
  box-shadow: 1px 1px 2px 0px rgba(0, 0, 0, 0.1);
  border-color: rgb(204, 204, 204);
}
.p-card-body {
  padding: 9px;
}
.link-title {
  font-weight: bold;
  font-size: 15px;
}

.p-card a {
  text-decoration: none;
}

.create-button {
  margin-top: 1em;
}

.create-card .p-card-body {
  display: flex;
}

.site {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
}
.site img,
.site .default-icon {
  margin-right: 6px;
}
.default-icon {
  width: 16px;
  height: 16px;
}
.site a {
  color: #999;
}
.link {
  font-weight: bold;
}
</style>


