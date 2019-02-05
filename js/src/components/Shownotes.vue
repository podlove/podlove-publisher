<template>
  <div>
    <shownotes-entry
      :entry="entry"
      v-on:update:entry="onUpdateEntry"
      v-on:delete:entry="onDeleteEntry"
      v-show="ready"
      v-for="entry in shownotes"
      :key="entry.id"
    ></shownotes-entry>

    <div class="p-card create-card" v-if="mode == 'create'">
      <div class="p-card-body">
        <input
          @keydown.enter.prevent="createEntry"
          @keydown.esc="mode = 'idle'"
          v-model="newUrl"
          type="text"
          name="new_entry"
          id="new_entry"
          placeholder="https://example.com"
          :disabled="mode == 'create-waiting'"
          v-focus
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
    },
    onUpdateEntry: function(entry) {
      const start = this.shownotes.findIndex(e => {
        return e.id == entry.id;
      });
      this.shownotes.splice(start, 1, entry);
    },
    onDeleteEntry: function(entry) {
      const start = this.shownotes.findIndex(e => {
        return e.id == entry.id;
      });
      this.shownotes.splice(start, 1);
    }
  },
  directives: {
    focus: {
      inserted: function(el) {
        el.focus();
      }
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
      window.scroll(
        0,
        document.getElementById("podlove-shownotes-app").offsetTop +
          document.body.clientHeight +
          500
      );
    }, 2500);
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
  display: flex;
  justify-content: space-between;
}
.failed {
  border-left: 3px solid #dc3232;
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
.actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}
.loading-sitename {
  height: 16px;
  width: 150px;
  background-color: #999;
  border-radius: 3px;
  opacity: 0.67;
}
.loading-link {
  height: 16px;
  width: 250px;
  background-color: #0074af;
  border-radius: 3px;
  opacity: 0.67;
}
.description {
  color: #666;
  font-style: italic;
}
</style>


