<template>
  <div class="shownotes-wrapper">
    <div v-if="mode == 'import-slacknotes'">
      <div class="shownotes-modal">
        <div class="shownotes-modal-content">
          <div class="header">
            <h1>Import from Slacknotes</h1>
            <div class="close" @click.prevent="mode = 'idle'">
              <icon-close></icon-close>
            </div>
          </div>
          <div class="content">
            <slacknotes
              mode="import"
              v-on:import:entries="onImportEntries"
            ></slacknotes>
          </div>
        </div>
      </div>
      <div class="shownotes-modal-backdrop"></div>
    </div>
    <div v-else>
      <div v-if="unfurlingProgress != 100" style="margin-bottom: 12px">
        Importing: {{ unfurlingProgress }}%
      </div>

      <draggable
        v-model="shownotes"
        @update="onDragEnd"
        :options="{ ghostClass: 'ghost', handle: '.drag-handle' }"
      >
        <shownotes-entry
          :entry="entry"
          v-on:update:entry="onUpdateEntry"
          v-on:delete:entry="onDeleteEntry"
          v-show="ready"
          v-for="entry in visibleShownotes"
          :key="entry.id"
        ></shownotes-entry>
      </draggable>

      <div class="p-expand" v-if="isTruncatedView">
        <a href="#" class="button" @click.prevent="isTruncatedView = false"
          >Expand to view all Shownotes</a
        >
      </div>

      <div class="p-card create-card" v-if="mode == 'create'">
        <div class="p-card-body">
          <div class="p-new-entry">
            <h3>Add new Entry</h3>
            <div class="p-entry-type-selector">
              <span>
                <input
                  type="radio"
                  id="entry-type-url"
                  value="link"
                  v-model="newEntryType"
                />
                <label for="entry-type-url">Link</label>
              </span>
              <span>
                <input
                  type="radio"
                  id="entry-type-topic"
                  value="topic"
                  v-model="newEntryType"
                />
                <label for="entry-type-topic">Topic</label>
              </span>
            </div>

            <div v-if="newEntryType == 'link'" class="p-new-entry-form">
              <input
                @keydown.enter.prevent="onCreateEntry"
                @keydown.esc="mode = 'idle'"
                v-model="newUrl"
                type="text"
                placeholder="https://example.com"
                :disabled="mode == 'create-waiting'"
                v-focus
              />
              <button
                type="button"
                class="button button-primary"
                @click.prevent="onCreateEntry"
                :disabled="mode == 'create-waiting'"
              >
                Add
              </button>
            </div>
            <div v-else-if="newEntryType == 'topic'" class="p-new-entry-form">
              <input
                @keydown.enter.prevent="onCreateEntry"
                @keydown.esc="mode = 'idle'"
                v-model="newTopic"
                type="text"
                placeholder="Topic, Subheading"
                :disabled="mode == 'create-waiting'"
                v-focus
              />
              <button
                type="button"
                class="button button-primary"
                @click.prevent="onCreateEntry"
                :disabled="mode == 'create-waiting'"
              >
                Add
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="footer">
        <button
          type="button"
          class="button create-button"
          @click.prevent="(isTruncatedView = false), (mode = 'create')"
          v-if="mode != 'create'"
        >
          Add Entry
        </button>

        <div>
          <button
            type="button"
            class="button create-button"
            @click.prevent="mode = 'import-slacknotes'"
            v-if="mode != 'create'"
          >
            Import from Slacknotes
          </button>

          <button
            type="button"
            class="button create-button"
            @click.prevent="importOsfShownotes"
            v-if="osf_active && mode != 'create'"
          >
            Import OSF Shownotes
          </button>

          <button
            type="button"
            class="button create-button"
            @click.prevent="importHTML"
            v-if="mode != 'create'"
          >
            Import from Episode HTML
          </button>

          <button
            type="button"
            class="button delete-button"
            @click.prevent="deleteAllEntries"
            v-if="mode != 'create'"
          >
            Delete all
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
const $ = jQuery;
import Close from "./icons/Close";

export default {
  props: ["episodeid"],
  data() {
    return {
      shownotes: [],
      ready: false,
      mode: "idle",
      newUrl: "",
      newTopic: "",
      isTruncatedView: true,
      truncatedThreshold: 5,
      newEntryType: "link",
    };
  },
  components: {
    "icon-close": Close,
  },
  methods: {
    createEntry: function (url, type, data) {
      let payload = { type: type, data: data, episode_id: this.episodeid };
      this.mode = "create-waiting";

      if (type == "link") {
        payload.original_url = url;
      }

      if (type == "topic") {
        payload.title = url;
      }

      $.post(podlove_vue.rest_url + "podlove/v1/shownotes", payload)
        .done((result) => {
          this.addIfNew(result);
          this.newUrl = "";
          this.mode = "idle";
        })
        .fail(({ responseJSON }) => {
          console.error("could not create entry:", responseJSON.message);
          this.mode = "idle";
        });
    },
    addIfNew: function (entry) {
      const isNewLink =
        entry.type == "link" &&
        this.shownotes.find((e) => e.original_url == entry.original_url) ===
          undefined;
      const isTopic = entry.type == "topic";

      if (isNewLink || isTopic) this.shownotes.push(entry);
    },
    onCreateEntry: function () {
      if (this.newEntryType == "link") {
        if (!this.newUrl) return;

        this.createEntry(this.newUrl, "link");
        this.newUrl = "";
      }

      if (this.newEntryType == "topic") {
        if (!this.newTopic) return;

        this.createEntry(this.newTopic, "topic");
        this.newTopic = "";
      }
    },
    onUpdateEntry: function (entry) {
      const start = this.shownotes.findIndex((e) => {
        return e.id == entry.id;
      });
      this.shownotes.splice(start, 1, entry);
    },
    onDeleteEntry: function (entry) {
      const start = this.shownotes.findIndex((e) => {
        return e.id == entry.id;
      });
      this.shownotes.splice(start, 1);
    },
    onImportEntries: function (entries) {
      this.mode = "idle";

      console.log("slack import", entries);

      let orderNumber = 0;
      entries.forEach(({ url: url, data: data }) => {
        orderNumber++;
        data.orderNumber = orderNumber;
        this.createEntry(url, "link", data);
      });
    },
    onDragEnd: function (e) {
      let newPosition = null;
      let prevEl = null;
      let nextEl = null;

      if (e.oldIndex == e.newIndex) {
        return;
      }

      if (e.newIndex == 0) {
        newPosition = this.shownotes[0].position - 1;
      } else if (e.newIndex == this.shownotes.length - 1) {
        newPosition =
          parseFloat(this.shownotes[this.shownotes.length - 1].position) + 1;
      } else {
        if (e.newIndex > e.oldIndex) {
          prevEl = this.shownotes[e.newIndex];
          nextEl = this.shownotes[e.newIndex + 1];
        } else {
          prevEl = this.shownotes[e.newIndex - 1];
          nextEl = this.shownotes[e.newIndex];
        }

        newPosition =
          (parseFloat(prevEl.position) + parseFloat(nextEl.position)) / 2.0;
      }

      newPosition = parseFloat(newPosition);
      this.shownotes[e.oldIndex].position = newPosition;

      const entry_id = this.shownotes[e.oldIndex].id;
      $.post(podlove_vue.rest_url + "podlove/v1/shownotes/" + entry_id, {
        id: entry_id,
        position: newPosition,
      }).fail(({ responseJSON }) => {
        console.error("could not update entry:", responseJSON.message);
      });
    },
    importOsfShownotes: function () {
      $.post(podlove_vue.rest_url + "podlove/v1/shownotes/osf", {
        post_id: podlove_vue.post_id,
      })
        .done((result) => {
          this.init(true);
        })
        .fail(({ responseJSON }) => {
          console.error("could not import osf:", responseJSON.message);
        });
    },
    importHTML: function () {
      $.post(podlove_vue.rest_url + "podlove/v1/shownotes/html", {
        post_id: podlove_vue.post_id,
      })
        .done((result) => {
          this.init();
        })
        .fail(({ responseJSON }) => {
          console.error("could not import html:", responseJSON.message);
        });
    },
    deleteAllEntries: function () {
      if (window.confirm("Permanently delete all shownotes entries?")) {
        this.shownotes.forEach((entry) =>
          jQuery.ajax({
            url: podlove_vue.rest_url + "podlove/v1/shownotes/" + entry.id,
            method: "DELETE",
            dataType: "json",
          })
        );
        this.shownotes = [];
      }
    },
    init: function (forceExpand = false) {
      $.getJSON(
        podlove_vue.rest_url +
          "podlove/v1/shownotes?episode_id=" +
          this.episodeid
      )
        .done((shownotes) => {
          this.shownotes = shownotes;
          this.ready = true;
          this.isTruncatedView =
            this.shownotes.length > this.truncatedThreshold && !forceExpand;
        })
        .fail(({ responseJSON }) => {
          console.error("could not load shownotes:", responseJSON.message);
        });
    },
  },
  computed: {
    visibleShownotes: function () {
      let shownotes = this.sortedShownotes;

      if (this.isTruncatedView) {
        shownotes = shownotes.slice(0, this.truncatedThreshold);
      }

      return shownotes;
    },
    unfurlingProgress: function () {
      const linkEntries = this.shownotes.filter(
        (entry) => entry.type == "link"
      );
      const linkCount = linkEntries.length;

      if (!linkCount) {
        return 100;
      }

      const unfurlingCount = linkEntries.filter(
        (entry) => entry.state == "unfurling"
      ).length;
      const progressPercent = Math.floor(
        (100 * (linkCount - unfurlingCount)) / linkCount
      );

      return progressPercent;
    },
    sortedShownotes: function () {
      return this.shownotes.sort((a, b) => {
        return a.position - b.position;
      });
    },
    osf_active: function () {
      return podlove_vue.osf_active;
    },
  },
  directives: {
    focus: {
      inserted: function (el) {
        el.focus();
      },
    },
  },
  mounted: function () {
    this.init();
  },
};
</script>

<style>
#podlove_podcast_shownotes .inside {
  background: #f9f9f9;
  margin-top: 0;
  padding-top: 6px;
}

.shownotes-wrapper {
  /* background: #f6f6f6;
  padding: 9px;*/
  padding-top: 6px;
}
.p-card {
  margin-bottom: 6px;
  box-shadow: 1px 1px 2px 0px rgba(0, 0, 0, 0.1);
  border-color: rgb(204, 204, 204);
}
.p-card-body {
  padding: 9px;
  display: flex;
  justify-content: space-between;
  min-height: 40px;
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
  box-shadow: 1px 1px 0px rgba(0, 0, 0, 0.2);
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
  height: 14px;
  width: 150px;
  background-color: #999;
  border-radius: 3px;
  opacity: 0.67;
}
.loading-link {
  height: 14px;
  width: 250px;
  background-color: #999;
  border-radius: 3px;
  opacity: 0.67;
}
.description {
  color: #666;
  font-style: italic;
}
.footer {
  display: flex;
  justify-content: space-between;
}

.shownotes-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  min-height: 360px;
  background: #000;
  opacity: 0.7;
  z-index: 119900;
}

.shownotes-modal {
  position: fixed;
  top: 30px;
  left: 30px;
  right: 30px;
  bottom: 30px;
  z-index: 120000;
}

.shownotes-modal * {
  box-sizing: content-box;
}

.shownotes-modal-content {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow: auto;
  min-height: 300px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.7);
  background: #fcfcfc;
  -webkit-font-smoothing: subpixel-antialiased;
}

.shownotes-modal-content {
  padding: 0 12px 12px 12px;
}

@media screen and (min-width: 960px) {
  .shownotes-modal-content {
    padding: 0 12px 12px 150px;
  }
}
.shownotes-modal-content .header {
  display: flex;
  justify-content: space-between;
}

.shownotes-modal-content .header .close {
  width: 50px;
  height: 50px;
  cursor: pointer;
}

.shownotes-modal .shownotes-modal-content h1 {
  padding: 0 16px;
  font-size: 22px;
  line-height: 50px;
  margin: 0;
}
.ghost,
.sortable-ghost {
  opacity: 1;
}
</style>
