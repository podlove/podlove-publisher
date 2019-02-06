<template>
  <div>
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
            <slacknotes mode="import" v-on:import:entries="onImportEntries"></slacknotes>
          </div>
        </div>
      </div>
      <div class="shownotes-modal-backdrop"></div>
    </div>
    <div v-else>
      <draggable
        v-model="shownotes"
        @end="onDragEnd"
        :options="{ghostClass: 'ghost', handle: '.drag-handle'}"
      >
        <shownotes-entry
          :entry="entry"
          v-on:update:entry="onUpdateEntry"
          v-on:delete:entry="onDeleteEntry"
          v-show="ready"
          v-for="entry in shownotes"
          :key="entry.id"
        ></shownotes-entry>
      </draggable>

      <div class="p-card create-card" v-if="mode == 'create'">
        <div class="p-card-body">
          <input
            @keydown.enter.prevent="onCreateEntry"
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
            @click.prevent="onCreateEntry"
            :disabled="mode == 'create-waiting'"
          >Add</button>
        </div>
      </div>

      <div class="footer">
        <button
          type="button"
          class="button create-button"
          @click.prevent="mode = 'create'"
          v-if="mode != 'create'"
        >Add Entry</button>
        
        <button
          type="button"
          class="button create-button"
          @click.prevent="mode = 'import-slacknotes'"
          v-if="mode != 'create'"
        >Import from Slacknotes</button>
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
      newUrl: ""
    };
  },
  components: {
    "icon-close": Close
  },
  methods: {
    createEntry: function(url) {
      this.mode = "create-waiting";

      $.post(podlove_vue.rest_url + "podlove/v1/shownotes", {
        original_url: url,
        episode_id: this.episodeid
      })
        .done(result => {
          this.addIfNew(result);
          this.newUrl = "";
          this.mode = "idle";
        })
        .fail(({ responseJSON }) => {
          console.error("could not create entry:", responseJSON.message);
          this.mode = "idle";
        });
    },
    addIfNew: function(entry) {
      const isNew =
        this.shownotes.find(e => e.original_url == entry.original_url) ===
        undefined;

      if (isNew) this.shownotes.push(entry);
    },
    onCreateEntry: function() {
      if (!this.newUrl) return;

      this.createEntry(this.newUrl);
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
    },
    onImportEntries: function(entries) {
      this.mode = "idle";

      // console.log("import", entries);
      entries.forEach(url => this.createEntry(url));
    },
    onDragEnd: function(e) {
      let newOrder = null;

      if (e.oldIndex == e.newIndex) {
        return;
      }

      if (e.newIndex == 0) {
        newOrder = this.shownotes[0].order - 1;
        console.log("moved to beginning of list");
      } else if (e.newIndex == this.shownotes.length - 1) {
        newOrder = this.shownotes[this.shownotes.length - 1].order + 1;
        console.log("moved to end of list");
      } else {
        let prevEl = this.shownotes[e.newIndex - 1];
        let nextEl = this.shownotes[e.newIndex + 1];
        newOrder = (prevEl.order + nextEl.order) / 2;
        console.log("moved somewhere");
      }

      console.log("new order", this.shownotes[e.newIndex], newOrder);

      // TODO: update element order

      // var itemEl = evt.item;  // dragged HTMLElement
      // evt.to;    // target list
      // evt.from;  // previous list
      // evt.oldIndex;  // element's old index within old parent
      // evt.newIndex;  // element's new index within new parent
      // console.log("dragEnd", e.oldIndex, e.newIndex, this.shownotes.length);
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
.ghost {
  opacity: 1;
}
</style>


