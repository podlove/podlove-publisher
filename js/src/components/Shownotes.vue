<template>
  <div class="shownotes-wrapper">
    <draggable
      v-if="moveModalVisible && topics.length > 0"
      id="podlove-shownotes-topic-modal"
      class="
        move-modal
        w-[350px]
        bg-[#000d]
        text-white text-base
        rounded-md
        p-2
      "
      :sort="false"
      :group="{ name: 'foo', pull: false }"
    >
      <div
        v-for="(topic, index) in topics"
        :key="topic.id"
        class="
          flex
          my-.5
          py-.5
          px-2
          rounded
          cursor-pointer
          hover:bg-[#4f9cff]
          sortable-chosen
        "
      >
        <div class="w-5 mr-1">{{ index }}</div>
        <div>{{ topic.title }}</div>
      </div>
    </draggable>
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
    <div v-else id="shownotes-main">
      <draggable
        v-model="shownotes"
        @update="onDragEnd"
        @end="onDragEnded"
        @clone="onClone"
        :group="{ name: 'foo', pull: 'clone' }"
        ghost-class="ghost"
        handle=".drag-handle"
        :animation="100"
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
        <sn-button
          :onClick="
            () => {
              isTruncatedView = false;
            }
          "
          >Expand to view all Shownotes</sn-button
        >
      </div>

      <sn-card v-if="mode == 'create'">
        <div>
          <div class="p-new-entry">
            <h3 style="margin-top: 0px">Add new Entry</h3>
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

            <div v-if="newEntryType == 'link'" class="flex">
              <input
                @keydown.enter.prevent="onCreateEntry"
                @keydown.esc="mode = 'idle'"
                v-model="newUrl"
                type="text"
                placeholder="https://example.com"
                :disabled="mode == 'create-waiting'"
                v-focus
                class="
                    w-full
                    shadow-sm
                    focus:ring-blue-500 focus:border-blue-500
                    block
                    sm:text-sm
                    border border-gray-300
                    rounded-md
                "
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
            <div v-else-if="newEntryType == 'topic'" class="flex">
              <input
                @keydown.enter.prevent="onCreateEntry"
                @keydown.esc="mode = 'idle'"
                v-model="newTopic"
                type="text"
                placeholder="Topic, Subheading"
                :disabled="mode == 'create-waiting'"
                v-focus
                class="
                    w-full
                    shadow-sm
                    focus:ring-blue-500 focus:border-blue-500
                    block
                    sm:text-sm
                    border border-gray-300
                    rounded-md
              "
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
      </sn-card>

      <div class="footer">
        <sn-button
          :onClick="
            () => {
              isTruncatedView = false;
              mode = 'create';
            }
          "
          v-if="mode != 'create'"
          htmlClass="max-w-3xl"
        >
          Add Entry
        </sn-button>

        <div>
          <button
            type="button"
            class="button create-button"
            @click.prevent="exportAsHTML"
            v-if="mode != 'create'"
          >
            Export as HTML
          </button>

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
import { saveAs } from "file-saver";
import { createPopper } from "@popperjs/core";
import SNButton from "./shownotes/sn-button.vue";
import SNCard from "./shownotes/sn-card.vue";

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
      truncatedThreshold: 10,
      newEntryType: "link",
      moveModalVisible: false,
    };
  },
  components: {
    "icon-close": Close,
    "sn-button": SNButton,
    "sn-card": SNCard,
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
    // onTopicDragEnd: function (e) {
    //   console.log("onTopicDragEnd", e);
    // },
    onClone: function (e) {
      if (document.getElementById("podlove-shownotes-app").offsetWidth < 1100) {
        // hide quicksort UI on small screens
        return;
      }

      this.moveModalVisible = true;

      window.setTimeout(() => {
        // init popper thing
        const tooltip = document.getElementById(
          "podlove-shownotes-topic-modal"
        );

        createPopper(e.item.querySelector(".drag-handle"), tooltip, {
          placement: "right",
          modifiers: [
            {
              name: "offset",
              options: {
                offset: [0, 750],
              },
            },
          ],
        });
        // console.log("onClone", { e });
        // console.log("onClone", e.clone);
      });
    },
    onDragEnded: function (e) {
      const findTopicIndex = (topic) => {
        return this.shownotes.findIndex(
          (entry) => entry.type == "topic" && entry.title == topic.title
        );
      };

      const getNewPosition = (newIndex) => {
        if (newIndex < 1) {
          // sort before first topic
          const nextTopic = this.topics[0];
          const nextTopicIndex = findTopicIndex(nextTopic);
          const lastEntryInTopic = this.shownotes[nextTopicIndex - 1];

          if (lastEntryInTopic) {
            // if there are already items:
            return (
              (parseFloat(lastEntryInTopic.position) +
                parseFloat(nextTopic.position)) /
              2.0
            );
          } else {
            // if it's the first item:
            return parseFloat(nextTopic.position) / 2.0;
          }
        } else {
          // sort to a topic
          const nextTopic = this.topics[newIndex];

          if (nextTopic) {
            const nextTopicIndex = findTopicIndex(nextTopic);
            const lastEntryInTopic = this.shownotes[nextTopicIndex - 1];

            return (
              (parseFloat(lastEntryInTopic.position) +
                parseFloat(nextTopic.position)) /
              2.0
            );
          } else {
            // if it's the last topic:
            return this.shownotes[this.shownotes.length - 1].position + 1;
          }
        }
      };

      this.moveModalVisible = false;

      if (e.to.id !== "podlove-shownotes-topic-modal") {
        return;
      }

      const newPosition = getNewPosition(e.newIndex);
      this.shownotes[e.oldIndex].position = newPosition;

      const entry_id = this.shownotes[e.oldIndex].id;
      $.post(podlove_vue.rest_url + "podlove/v1/shownotes/" + entry_id, {
        id: entry_id,
        position: newPosition,
      }).fail(({ responseJSON }) => {
        console.error("could not update entry:", responseJSON.message);
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
    exportAsHTML: function () {
      $.get(podlove_vue.rest_url + "podlove/v1/shownotes/render/html", {
        post_id: podlove_vue.post_id,
      })
        .done((result) => {
          var blob = new Blob([result], { type: "text/html;charset=utf-8" });
          saveAs(blob, "shownotes.html");
        })
        .fail(({ responseJSON }) => {
          console.error("could not generate html:", responseJSON.message);
        });
    },
  },
  computed: {
    topics: function () {
      return this.shownotes.filter((entry) => entry.type == "topic");
    },
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

#podlove_podcast_shownotes a {
  color: inherit;
  text-decoration: inherit;
}

.sortable-chosen.ghost > div > div > div {
  background-color: #eee;
}
.sortable-chosen.ghost > div > div > div > div {
  visibility: hidden;
}

/* BEGIN shownotes modal */
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
  top: 100px;
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
/* END shownotes modal */

/* BEGIN temporary rules until converted to Tailwind */
#podlove_podcast_shownotes .p-expand {
  margin: 40px 24px;
}

#podlove_podcast_shownotes .footer {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  margin: 0px 24px;
}

/* END temporary rules */

#podlove_podcast_shownotes *,
#podlove_podcast_shownotes :before,
#podlove_podcast_shownotes :after {
  box-sizing: border-box;
  border-width: 0;
  border-style: solid;
  border-color: currentColor;
}

.left-\[1070px\] {
  left: 1070px;
}

.top-\[160px\] {
  top: 160px;
}

.mx-5 {
  margin-left: 1.25rem;
  margin-right: 1.25rem;
}

.my-3 {
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
}

.mx-\[3\.125rem\] {
  margin-left: 3.125rem;
  margin-right: 3.125rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mt-8 {
  margin-top: 2rem;
}

.mb-0 {
  margin-bottom: 0;
}

.mt-1 {
  margin-top: 0.25rem;
}

.ml-3 {
  margin-left: 0.75rem;
}

.mt-2\.5 {
  margin-top: 0.625rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.mt-6 {
  margin-top: 1.5rem;
}

.block {
  display: block;
}

.flex {
  display: flex;
}

.inline-flex {
  display: inline-flex;
}

.hidden {
  display: none;
}

.h-5 {
  height: 1.25rem;
}

.h-8 {
  height: 2rem;
}

.h-4 {
  height: 1rem;
}

.h-6 {
  height: 1.5rem;
}

.max-h-48 {
  max-height: 12rem;
}

.w-\[350px\] {
  width: 350px;
}

.w-5 {
  width: 1.25rem;
}

.w-full {
  width: 100%;
}

.w-4 {
  width: 1rem;
}

.w-\[300px\] {
  width: 300px;
}

.w-36 {
  width: 9rem;
}

.max-w-3xl {
  max-width: 48rem;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.flex-grow {
  flex-grow: 1;
}

.origin-center {
  transform-origin: center;
}

.transform {
  transform: var(--tw-transform);
}

@-webkit-keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  -webkit-animation: spin 1s linear infinite;
  animation: spin 1s linear infinite;
}

@-webkit-keyframes pulse {
  50% {
    opacity: 0.5;
  }
}

@keyframes pulse {
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  -webkit-animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.cursor-pointer {
  cursor: pointer;
}

.cursor-move {
  cursor: move;
}

.items-center {
  align-items: center;
}

.justify-end {
  justify-content: flex-end;
}

.justify-center {
  justify-content: center;
}

.justify-between {
  justify-content: space-between;
}

.gap-3 {
  gap: 0.75rem;
}

.gap-1 {
  gap: 0.25rem;
}

.gap-2\.5 {
  gap: 0.625rem;
}

.gap-2 {
  gap: 0.5rem;
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rounded-md {
  border-radius: 0.375rem;
}

.rounded {
  border-radius: 0.25rem;
}

#podlove_podcast_shownotes .border {
  border-width: 1px;
}

#podlove_podcast_shownotes .border-b-2 {
  border-bottom-width: 2px;
}

#podlove_podcast_shownotes .border-b {
  border-bottom-width: 1px;
}

#podlove_podcast_shownotes .border-l-4 {
  border-left-width: 4px;
}

#podlove_podcast_shownotes .border-gray-800 {
  --tw-border-opacity: 1;
  border-color: rgb(31 41 55 / var(--tw-border-opacity));
}

#podlove_podcast_shownotes .border-gray-300 {
  --tw-border-opacity: 1;
  border-color: rgb(209 213 219 / var(--tw-border-opacity));
}

#podlove_podcast_shownotes .border-transparent {
  border-color: transparent;
}

#podlove_podcast_shownotes .border-red-400 {
  --tw-border-opacity: 1;
  border-color: rgb(248 113 113 / var(--tw-border-opacity));
}

.bg-\[\#000d\] {
  background-color: #000d;
}

.bg-white {
  --tw-bg-opacity: 1;
  background-color: rgb(255 255 255 / var(--tw-bg-opacity));
}

.bg-red-50 {
  --tw-bg-opacity: 1;
  background-color: rgb(254 242 242 / var(--tw-bg-opacity));
}

.bg-blue-600 {
  --tw-bg-opacity: 1;
  background-color: rgb(37 99 235 / var(--tw-bg-opacity));
}

.bg-gray-300 {
  --tw-bg-opacity: 1;
  background-color: rgb(209 213 219 / var(--tw-bg-opacity));
}

.bg-gray-100 {
  --tw-bg-opacity: 1;
  background-color: rgb(243 244 246 / var(--tw-bg-opacity));
}

.p-2 {
  padding: 0.5rem;
}

.p-4 {
  padding: 1rem;
}

.px-2 {
  padding-left: 0.5rem;
  padding-right: 0.5rem;
}

.px-3 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.py-2\.5 {
  padding-top: 0.625rem;
  padding-bottom: 0.625rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.px-4 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.pt-5 {
  padding-top: 1.25rem;
}

.text-base {
  font-size: 1rem;
  line-height: 1.5rem;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.font-bold {
  font-weight: 700;
}

.font-medium {
  font-weight: 500;
}

.text-white {
  --tw-text-opacity: 1;
  color: rgb(255 255 255 / var(--tw-text-opacity));
}

.text-gray-500 {
  --tw-text-opacity: 1;
  color: rgb(107 114 128 / var(--tw-text-opacity));
}

.text-gray-700 {
  --tw-text-opacity: 1;
  color: rgb(55 65 81 / var(--tw-text-opacity));
}

.text-red-700 {
  --tw-text-opacity: 1;
  color: rgb(185 28 28 / var(--tw-text-opacity));
}

.text-gray-800 {
  --tw-text-opacity: 1;
  color: rgb(31 41 55 / var(--tw-text-opacity));
}

.text-gray-400 {
  --tw-text-opacity: 1;
  color: rgb(156 163 175 / var(--tw-text-opacity));
}

.text-red-400 {
  --tw-text-opacity: 1;
  color: rgb(248 113 113 / var(--tw-text-opacity));
}

.opacity-60 {
  opacity: 0.6;
}

#podlove_podcast_shownotes .shadow {
  --tw-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px 0 rgb(0 0 0 / 0.06);
  box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
}

#podlove_podcast_shownotes .shadow-sm {
  --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
}

.line-clamp-4 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 4;
}

.hover\:bg-\[\#4f9cff\]:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(79 156 255 / var(--tw-bg-opacity));
}

.hover\:bg-red-200:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(254 202 202 / var(--tw-bg-opacity));
}

.hover\:bg-gray-50:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(249 250 251 / var(--tw-bg-opacity));
}

.hover\:bg-blue-700:hover {
  --tw-bg-opacity: 1;
  background-color: rgb(29 78 216 / var(--tw-bg-opacity));
}

.focus\:border-blue-500:focus {
  --tw-border-opacity: 1;
  border-color: rgb(59 130 246 / var(--tw-border-opacity));
}

.focus\:outline-none:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
}

.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0
    var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0
    calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow),
    var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-blue-500:focus {
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(59 130 246 / var(--tw-ring-opacity));
}

.focus\:ring-red-500:focus {
  --tw-ring-opacity: 1;
  --tw-ring-color: rgb(239 68 68 / var(--tw-ring-opacity));
}

.focus\:ring-offset-2:focus {
  --tw-ring-offset-width: 2px;
}

@media (min-width: 640px) {
  .sm\:text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
  }
}
</style>
